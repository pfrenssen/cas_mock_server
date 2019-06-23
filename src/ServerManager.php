<?php

declare(strict_types = 1);

namespace Drupal\cas_mock_server;

use Drupal\Core\State\StateInterface;

/**
 * Service providing methods to start and stop the mock server.
 */
class ServerManager implements ServerManagerInterface {

  /**
   * The key that identifies the state of the mock server in the state storage.
   */
  const STATE_KEY_SERVER_STATE = 'cas_mock_server.state';

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a ServerManager.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function start(): void {
    if ($this->isServerActive()) {
      return;
    }

    $this->state->set(self::STATE_KEY_SERVER_STATE, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function stop(): void {
    if (!$this->isServerActive()) {
      return;
    }

    $this->state->set(self::STATE_KEY_SERVER_STATE, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isServerActive(): bool {
    return $this->state->get(self::STATE_KEY_SERVER_STATE, FALSE);
  }

}
