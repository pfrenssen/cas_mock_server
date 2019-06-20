<?php

declare(strict_types = 1);

namespace Drupal\cas_mock_server;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;

/**
 * Service providing methods to start and stop the mock server.
 */
class ServerManager implements ServerManagerInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a ServerManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state) {
    $this->configFactory = $config_factory;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function start(): void {
    $this->state->set('cas_mock_server.state', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function stop(): void {
    $this->state->set('cas_mock_server.state', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isServerActive(): bool {
    return $this->state->get('cas_mock_server.state', FALSE);
  }

}
