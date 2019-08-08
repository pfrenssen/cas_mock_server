<?php

declare(strict_types = 1);

namespace Drupal\cas_mock_server\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\cas_mock_server\ServerManagerInterface;
use Drupal\cas_mock_server\UserManagerInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commands to manage the CAS mock server.
 */
class CasMockServerCommands extends DrushCommands {

  /**
   * The CAS mock server manager.
   *
   * @var \Drupal\cas_mock_server\ServerManagerInterface
   */
  protected $serverManager;

  /**
   * The CAS mock user manager.
   *
   * @var \Drupal\cas_mock_server\UserManagerInterface
   */
  protected $userManager;

  /**
   * Constructs a CasMockServerCommands object.
   *
   * @param \Drupal\cas_mock_server\ServerManagerInterface $serverManager
   *   The CAS mock server manager.
   * @param \Drupal\cas_mock_server\UserManagerInterface $userManager
   *   The CAS mock user manager.
   */
  public function __construct(ServerManagerInterface $serverManager, UserManagerInterface $userManager) {
    parent::__construct();
    $this->serverManager = $serverManager;
    $this->userManager = $userManager;
  }

  /**
   * Starts the mock server.
   *
   * @return int
   *   An integer to be used as an exit code.
   *
   * @command cas-mock-server:start
   * @aliases casms-start
   */
  public function start(): int {
    $this->serverManager->start();

    $this->logger()->info(dt('The CAS mock server is active'));

    return 0;
  }

  /**
   * Stops the mock server.
   *
   * @return int
   *   An integer to be used as an exit code.
   *
   * @command cas-mock-server:stop
   * @aliases casms-stop
   */
  public function stop(): int {
    $this->serverManager->stop();

    $this->logger()->notice(dt('The CAS mock server is active'));

    return 0;
  }

  /**
   * Resets the mock server, clearing all mock users.
   *
   * @return int
   *   An integer to be used as an exit code.
   *
   * @command cas-mock-server:reset
   * @aliases casms-reset
   */
  public function reset(): int {
    $this->userManager->deleteUsers();

    $this->logger()->notice(dt('Deleted mock CAS users'));

    return 0;
  }

  /**
   * Reports the status of the mock server.
   *
   * @return int
   *   The status, to be returned as an error code for machine reading:
   *   - 0: Server is active.
   *   - 1: Server is inactive.
   *
   * @command cas-mock-server:status
   * @aliases casms-status
   */
  public function status(): int {
    $status = $this->serverManager->isServerActive();

    if ($status) {
      $this->logger()->notice(dt('The CAS mock server is active'));
      return 0;
    }
    else {
      $this->logger()->notice(dt('The CAS mock server is inactive'));
      return 1;
    }
  }

  /**
   * Returns a list of CAS mock users.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   A table containing all CAS mock users.
   *
   * @command cas-mock-server:user-list
   * @aliases casms-ul
   * @table-style default
   * @usage drush cas-mock-server:user-list --format=yaml
   *   Output the list of mock users in YAML format.
   */
  public function list($options = ['format' => 'table']): RowsOfFields {
    $users = $this->userManager->getUsers();

    // Compile a list of all attributes used across the different users, making
    // sure the required attributes (username, email, password) are listed
    // first.
    $attributes = array_reduce($users, function (array $carry, array $user): array {
        return array_unique(array_merge($carry, array_keys($user)));
    }, ['username', 'email', 'password']);

    $rows = [];
    foreach ($users as $user) {
      $rows[] = array_merge(array_fill_keys($attributes, NULL), $user);
    }

    return new RowsOfFields($rows);
  }

  /**
   * Creates a mock user.
   *
   * @param string $username
   *   The username for the new mock user.
   * @param array $options
   *   Options for the command. See the @option annotations.
   *
   * @return int
   *   The status, to be returned as an error code for machine reading:
   *   - 0: The user has been created.
   *   - 1: An attribute option without the attribute name has been passed.
   *   - 2: An error occurred while creating the user.
   *
   * @command cas-mock-server:user-create
   * @option email The email address for the new mock user
   * @option password The password for the new mock user
   * @option attribute Additional attributes to be added to the CAS account.
   *   Multiple attributes can be passed by adding more than one --attribute
   *   options. Each attribute consists of the attribute name concatenated with
   *   the attribute value and using the semicolon (:) as separator. Empty
   *   attribute value are allowed. Also, unlike the attribute name, the
   *   value can contain the semicolon (:) char.
   * @aliases casms-uc
   * @usage drush casms-uc myuser --email="user@example.com" --password="mypass"
   *   Creates a new mock user with the user name mockuser, the email address
   *   user@example.com, and the password mypass
   * @usage drush cas-mock-server:user-create myuser --email="user@example.com" --password="mypass" --attribute=firstname:Joe --attribute=lastname:Doe
   *   Creates a new mock user with the user name mockuser, the email address
   *   user@example.com, the password mypass, firstname Joe, and lastname Doe
   */
  public function create($username, array $options = ['email' => self::REQ, 'password' => self::REQ, 'attribute' => []]): int {
    $user_data = [
      'username' => $username,
      'email' => $options['email'],
      'password' => $options['password'],
    ];

    // Add additional attributes.
    foreach ($options['attribute'] as $attribute) {
      // The attribute value could contain the semicolon (:) char.
      $parts = explode(':', $attribute, 2);
      // The attribute name could not be empty.
      if (!$parts[0]) {
        $this->logger()->error("Invalid option --attribute=$attribute. Missing the attribute name.");
        return 1;
      }
      // The attribute value could be empty.
      $parts[1] = $parts[1] ?? NULL;

      $user_data[$parts[0]] = $parts[1];
    }

    try {
      $this->userManager->addUser($user_data);
      return 0;
    }
    catch (\Exception $e) {
      $this->logger()->error($e->getMessage());
      return 2;
    }
  }

}
