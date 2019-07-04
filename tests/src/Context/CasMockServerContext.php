<?php

namespace Drupal\Tests\cas_mock_server\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\cas_mock_server\ServerManagerInterface;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\cas_mock_server\UserManagerInterface;
use Drupal\DrupalExtension\Context\RawDrupalContext;

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
   * A list of usernames that were created by a scenario.
   *
   * These are being tracked so they can be cleaned up after the scenario ends.
   *
   * @var string[]
   */
  protected $usernames = [];

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
  public function startMockServer(BeforeScenarioScope $scope) {
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
  public function stopMockServer() {
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
  public function cleanCasUsers() {
    // Early bailout if there are no users to clean up.
    if (empty($this->usernames)) {
      return;
    }

    // Delete the users for the mock user storage.
    $user_manager = $this->getCasMockServerUserManager();
    $user_manager->deleteUsers($this->usernames);

    // Delete users that might have been created in Drupal after logging in
    // through CAS.
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $user_ids = $user_storage->getQuery()->condition('name', $this->usernames, 'IN')->execute();
    $users = $user_storage->loadMultiple($user_ids);
    if (!empty($users)) {
      foreach ($users as $user) {
        user_cancel([], $user->id(), 'user_cancel_delete');
      }
      $this->getDriver()->processBatch();
    }

    $this->usernames = [];
  }

  /**
   * Returns the server manager service.
   *
   * @return \Drupal\cas_mock_server\ServerManagerInterface
   *   The service that manages the CAS mock server.
   */
  protected function getServerManager() {
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
   * | Username | E-mail          | Password       | First name | Last name |
   * | chuck    | chuck@norris.eu | Qwerty         | Chuck      | Norris    |
   * | jb007    | 007@mi6.eu      | shaken_stirred | James      | Bond      |
   * @codingStandardsIgnoreEnd
   *
   * The `Username`, `E-mail` and `Password` columns are required. All other
   * attributes are user defined.
   *
   * The CAS module might create Drupal user accounts for these users on a
   * successful authentication. At the end of the scenario the Drupal user
   * accounts that match these usernames will be cleaned up.
   *
   * @param \Behat\Gherkin\Node\TableNode $users_data
   *   The users to register.
   *
   * @Given (the following )CAS users:
   */
  public function registerUsers(TableNode $users_data) {
    $users = [];
    $attributes_map = $this->getAttributesMap();

    foreach ($users_data->getColumnsHash() as $user_data) {
      $values = [];
      // Replace the human readable column headers with the machine names of the
      // attributes.
      foreach ($user_data as $key => $value) {
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

      // Keep track of the users that are created so they can be cleaned up
      // after the test.
      $this->usernames[] = $values['username'];
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
  protected function getAttributesMap() {
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
  protected function getCasMockServerUserManager() {
    return \Drupal::service('cas_mock_server.user_manager');
  }

}
