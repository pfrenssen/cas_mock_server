<?php

declare(strict_types = 1);

namespace Drupal\cas_mock_server;

use Drupal\cas_mock_server\Config\CasMockServerConfigOverrider;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
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
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a ServerManager.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(StateInterface $state, CacheTagsInvalidatorInterface $cacheTagsInvalidator, ConfigFactoryInterface $configFactory) {
    $this->state = $state;
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function start(): void {
    if ($this->isServerActive()) {
      return;
    }

    $this->state->set(self::STATE_KEY_SERVER_STATE, TRUE);
    $this->invalidateCache();
  }

  /**
   * {@inheritdoc}
   */
  public function stop(): void {
    if (!$this->isServerActive()) {
      return;
    }

    $this->state->set(self::STATE_KEY_SERVER_STATE, FALSE);
    $this->invalidateCache();
  }

  /**
   * {@inheritdoc}
   */
  public function isServerActive(): bool {
    return $this->state->get(self::STATE_KEY_SERVER_STATE, FALSE);
  }

  /**
   * Invalidates caches that contain config overridden by us.
   *
   * We are overriding the configuration of the CAS module so that it uses our
   * mock server when it is activated. When the mock server is enabled or
   * disabled we need to invalidate any caches that contain the CAS module
   * config so that it will not make a server connection using stale data.
   */
  protected function invalidateCache(): void {
    $this->cacheTagsInvalidator->invalidateTags([CasMockServerConfigOverrider::CACHE_TAG]);

    // The config factory static cache does not yet support cacheability
    // metadata. We need to invalidate its cache directly.
    $this->configFactory->reset('cas.settings');
  }

}
