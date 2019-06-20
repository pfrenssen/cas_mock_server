<?php

declare(strict_types = 1);

namespace Drupal\cas_mock_server\Commands;

use Drupal\cas_mock_server\Exception\CasMockServerException;
use Drupal\cas_mock_server\Exception\UnresolvableHostException;
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
   * @return int
   *   An integer to be used as an exit code. The following values may be
   *   returned:
   *   - 0: Server started without errors.
   *   - 1: Server could not be started because the hostname is not resolvable.
   *   - 2: An error occurred while starting the server.
   *
   * @command cas-mock-server:start
   * @aliases casms-start
   * @usage drush cas-mock-server:start --uri=http://mysite.local
   *   Starts the mock server. Pass the base URL of the Drupal site using the
   *   --uri option.
   */
  public function start(): int {
    try {
      $this->serverManager->start();
    }
    catch (UnresolvableHostException $e) {
      $this->logger()->error('The server could not be started because the hostname could not be determined. Use the --uri option to pass the base URL of the Drupal site.');
      return 1;
    }
    catch (CasMockServerException $e) {
      $this->logger()->error($e->getMessage());
      return 2;
    }

    return 0;
  }

  /**
   * Stops the mock server.
   *
   * @return int
   *   An integer to be used as an exit code. The following values may be
   *   returned:
   *   - 0: Server started without errors.
   *   - 1: An error occurred while stopping the server.
   *
   * @command cas-mock-server:stop
   * @aliases casms-stop
   */
  public function stop(): int {
    try {
      $this->serverManager->stop();
    }
    catch (CasMockServerException $e) {
      $this->logger()->error($e->getMessage());
      return 1;
    }

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

}
