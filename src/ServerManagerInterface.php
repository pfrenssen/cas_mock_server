<?php

declare(strict_types = 1);

namespace Drupal\cas_mock_server;

/**
 * Interface for services providing methods to start and stop the mock server.
 */
interface ServerManagerInterface {

  /**
   * Starts the mock server.
   *
   * @throws \Drupal\cas_mock_server\Exception\CasMockServerException
   *   Thrown when an error occurs starting the server.
   * @throws \Drupal\cas_mock_server\Exception\UnresolvableHostException
   *   Thrown when the server is being started using an unresolvable host.
   */
  public function start(): void;

  /**
   * Stops the mock server.
   *
   * @throws \Drupal\cas_mock_server\Exception\CasMockServerException
   *   Thrown when an error occurs when stopping the server.
   */
  public function stop(): void;

  /**
   * Returns whether or not the server is active.
   *
   * @return bool
   *   TRUE when the server is active.
   */
  public function isServerActive(): bool;

}
