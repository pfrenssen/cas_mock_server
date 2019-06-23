<?php

declare(strict_types = 1);

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
    return (int) $this->serverManager->isServerActive();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
