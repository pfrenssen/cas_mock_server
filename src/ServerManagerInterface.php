<?php

namespace Drupal\cas_mock_server;

/**
 * Interface for services providing methods to start and stop the mock server.
 */
interface ServerManagerInterface {

  /**
   * Starts the mock server.
   */
  public function start();

  /**
   * Stops the mock server.
   */
  public function stop();

  /**
   * Returns whether or not the server is active.
   *
   * @return bool
   *   TRUE when the server is active.
   */
  public function isServerActive();

}
