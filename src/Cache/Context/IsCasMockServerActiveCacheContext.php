<?php

namespace Drupal\cas_mock_server\Cache\Context;

use Drupal\cas_mock_server\ServerManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Defines a cache context that indicated if the CAS mock server is active.
 *
 * Cache context ID: 'cas_mock_server_is_active'.
 */
class IsCasMockServerActiveCacheContext implements CacheContextInterface {

  /**
   * The context value that indicates that the mock server is active.
   */
  const SERVER_ACTIVE = 'active';

  /**
   * The context value that indicates that the mock server is inactive.
   */
  const SERVER_INACTIVE = 'inactive';

  /**
   * The CAS mock server manager.
   *
   * @var \Drupal\cas_mock_server\ServerManagerInterface
   */
  protected $serverManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ServerManagerInterface $serverManager) {
    $this->serverManager = $serverManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Is CAS mock server active');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->serverManager->isServerActive() ? self::SERVER_ACTIVE : self::SERVER_INACTIVE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
