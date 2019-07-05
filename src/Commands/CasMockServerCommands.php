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

}
