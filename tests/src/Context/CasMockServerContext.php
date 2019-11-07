<?php

declare(strict_types = 1);

namespace Drupal\Tests\cas_mock_server\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\cas_mock_server\ServerManagerInterface;
use Drupal\cas_mock_server\UserManagerInterface;

/**
 * Step definitions for using the CAS mock server in Behat scenarios.
 */
class CasMockServerContext extends RawDrupalContext {

  /**
   * Whether the mock server should be disabled when the scenario ends.
   *
   * @var bool
   */
  protected $disableMockServerAfterScenario = FALSE;

  /**
   * Human readable attribute labels keyed by CAS attribute machine names.
   *
   * Do not access this property directly, use ::getAttributesMap() instead.
   *
   * @var array
   */
  protected $attributesMap;

  /**
   * A list of users that were created by a scenario.
   *
   * These are being tracked so they can be cleaned up after the scenario ends.
   * The array is an associative array keyed by username and having the user
   * email as values.
   *
   * @var string[]
   */
  protected $users = [];

  /**
   * Constructs a new CasMockServerContext object.
   *
   * @param array $attributes_map
   *   An associative array of human readable attributes, keyed by CAS attribute
   *   machine names.
   */
  public function __construct(array $attributes_map) {
    $this->attributesMap = $attributes_map;
  }

  /**
   * Enables the mock server.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The before scenario scope.
   *
   * @BeforeScenario @casMockServer
   */
  public function startMockServer(BeforeScenarioScope $scope): void {
    if (!$this->getServerManager()->isServerActive()) {
      $this->disableMockServerAfterScenario = TRUE;
      $this->getServerManager()->start();
    }
  }

  /**
   * Disables the mock server.
   *
   * @AfterScenario @casMockServer
   */
  public function stopMockServer(): void {
    if ($this->disableMockServerAfterScenario) {
      $this->disableMockServerAfterScenario = FALSE;
      $this->getServerManager()->stop();
    }
  }

  /**
   * Clean up any CAS users created in the scenario.
   *
   * @AfterScenario @casMockServer
   */
  public function cleanCasUsers(): void {
    // Early bailout if there are no users to clean up.
    if (empty($this->users)) {
      return;
    }

    // Delete the users for the mock user storage.
    $user_manager = $this->getCasMockServerUserManager();
    $user_manager->deleteUsers(array_keys($this->users));

    // Delete users that might have been created in Drupal after logging in
    // through CAS.
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $query = $user_storage->getQuery();
    $or_condition = $query->orConditionGroup()
      ->condition('name', array_keys($this->users), 'IN')
      // Some users, created in Drupal, might have a different name than the CAS
      // user name as some event subscribers are able to alter them. Do an
      // additional check by email.
      ->condition('mail', array_filter(array_values($this->users)), 'IN');

    $user_ids = $user_storage->getQuery()->condition($or_condition)->execute();
    $users = $user_storage->loadMultiple($user_ids);
    if (!empty($users)) {
      foreach ($users as $user) {
        user_cancel([], $user->id(), 'user_cancel_delete');
      }
      $this->getDriver()->processBatch();
    }

    $this->users = [];
  }

  /**
   * Returns the server manager service.
   *
   * @return \Drupal\cas_mock_server\ServerManagerInterface
   *   The service that manages the CAS mock server.
   */
  protected function getServerManager(): ServerManagerInterface {
    return \Drupal::service('cas_mock_server.server_manager');
  }

  /**
   * Registers users in the mock CAS service.
   *
   * This takes a table of user attribute table, with the first row containing
   * human readable headers that are defined in the `attributes_map` parameter
   * in `behat.yml`. Example table format:
   *
   * @codingStandardsIgnoreStart
   * | Username | E-mail          | Password       | First name | Last name | Local username |
   * | chuck    | chuck@norris.eu | Qwerty         | Chuck      | Norris    | chuck_local    |
   * | jb007    | 007@mi6.eu      | shaken_stirred | James      | Bond      |                |
   * @codingStandardsIgnoreEnd
   *
   * The `Username`, `E-mail` and `Password` columns are required. All other
   * attributes are user defined. The optional `Local username` can be used to
   * link the CAS user to an existing Drupal account.
   *
   * The CAS module might create Drupal user accounts for these users on a
   * successful authentication. At the end of the scenario the Drupal user
   * accounts that match these usernames will be cleaned up.
   *
   * @param \Behat\Gherkin\Node\TableNode $users_data
   *   The users to register.
   *
   * @throws \Exception
   *   If non-existing local username has been passed.
   *
   * @Given (the following )CAS users:
   */
  public function registerUsers(TableNode $users_data): void {
    $users = [];
    $attributes_map = $this->getAttributesMap();

    /** @var \Drupal\externalauth\ExternalAuthInterface $external_auth */
    $external_auth = \Drupal::service('externalauth.externalauth');

    foreach ($users_data->getColumnsHash() as $user_data) {
      $values = [];
      $local_username = NULL;
      // Replace the human readable column headers with the machine names of the
      // attributes.
      foreach ($user_data as $key => $value) {
        if ($key === 'Local username' && $value) {
          $local_username = $value;
          continue;
        }

        if (array_key_exists($key, $attributes_map)) {
          $values[$attributes_map[$key]] = $value;
        }
        else {
          throw new \RuntimeException("Unknown attribute '$key' in user table. Declare it in behat.yml in the attributes_map parameter.");
        }
      };

      // Check that the required attributes are present.
      $missing_attributes = array_diff(UserManagerInterface::REQUIRED_ATTRIBUTES, array_keys($values));
      if (!empty($missing_attributes)) {
        $missing_attributes = implode(',', $missing_attributes);
        throw new \RuntimeException("Required attributes '$missing_attributes' are missing in the user table.");
      }

      $users[$values['username']] = $values;

      if ($local_username) {
        /** @var \Drupal\user\UserInterface $local_account */
        $local_account = user_load_by_name($local_username);
        if (!$local_account) {
          throw new \Exception("Non-existing Drupal user '$local_username'.");
        }
        $external_auth->linkExistingAccount($values['username'], 'cas', $local_account);
      }

      // Keep track of the users that are created so they can be cleaned up
      // after the test.
      $this->users[$values['username']] = $values['email'];
    }
    $this->getCasMockServerUserManager()->addUsers($users);
  }

  /**
   * Returns the array that maps human readable attributes to machine names.
   *
   * @return array
   *   An associative array of CAS attribute names, keyed by human readable
   *   attribute labels.
   */
  protected function getAttributesMap(): array {
    // Provide default values for the required attributes.
    return array_flip($this->attributesMap + [
      'username' => 'Username',
      'email' => 'E-mail',
      'password' => 'Password',
    ]);
  }

  /**
   * Returns the user manager for the CAS mock server.
   *
   * @return \Drupal\cas_mock_server\UserManagerInterface
   *   The user manager.
   */
  protected function getCasMockServerUserManager(): UserManagerInterface {
    return \Drupal::service('cas_mock_server.user_manager');
  }

}
