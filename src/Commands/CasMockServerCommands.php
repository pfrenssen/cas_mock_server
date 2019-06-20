<?php

declare(strict_types = 1);

namespace Drupal\cas_mock_server\Commands;

use Drupal\cas_mock_server\ServerManagerInterface;
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
   * Constructs a CasMockServerCommands object.
   *
   * @param ServerManagerInterface $serverManager
   *   The CAS mock server manager.
   */
  public function __construct(ServerManagerInterface $serverManager) {
    parent::__construct();
    $this->serverManager = $serverManager;
  }

  /**
   * Starts the mock server.
   *
   * @command cas-mock-server:start
   * @aliases casms-start
   */
  public function start(): void {
    $this->serverManager->start();
  }

  /**
   * Stops the mock server.
   *
   * @command cas-mock-server:stop
   * @aliases casms-stop
   */
  public function stop(): void {
    $this->serverManager->stop();
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

}
