<?php

namespace Drupal\cas_mock_server\Config;

use Drupal\cas\Service\CasHelper;
use Drupal\cas_mock_server\ServerManagerInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Overrides the CAS module config to use the endpoint of the CAS mock server.
 *
 * If the CAS module is not enabled this overrider will have no effect since the
 * config factory will never ask us to override the module configuration.
 */
class CasMockServerConfigOverrider implements ConfigFactoryOverrideInterface {

  /**
   * The cache tag that is used to identify overridden config.
   */
  const CACHE_TAG = 'cas_mock_server_config_overrider';

  /**
   * The CAS mock server manager.
   *
   * @var \Drupal\cas_mock_server\ServerManagerInterface
   */
  protected $serverManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a CasMockServerConfigOverrider.
   *
   * @param \Drupal\cas_mock_server\ServerManagerInterface $serverManager
   *   The CAS mock server manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ServerManagerInterface $serverManager, RequestStack $requestStack, MessengerInterface $messenger) {
    $this->serverManager = $serverManager;
    $this->requestStack = $requestStack;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    // Only override config if the server is active.
    if (in_array('cas.settings', $names) && $this->serverManager->isServerActive()) {
      $overrides['cas.settings'] = $this->getOverrides();
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'cas_mock_server';
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    $metadata = new CacheableMetadata();

    if ($name === 'cas.settings') {
      $metadata->addCacheContexts(['cas_mock_server_is_active']);
      $metadata->addCacheTags([self::CACHE_TAG]);
    }
    return $metadata;
  }

  /**
   * Returns the configuration of the mock server.
   *
   * @return array
   *   An associative array of configuration for the CAS module, in order to
   *   override the production CAS server with the mock server.
   */
  protected function getOverrides() {
    $overrides = [];

    $request = $this->requestStack->getCurrentRequest();
    $hostname = $request->getHost();

    if (!$this->isResolvable($hostname)) {
      $this->messenger->addError('Could not resolve the hostname "' . $hostname . '" for the CAS mock server.');
      return $overrides;
    }

    $overrides['server']['hostname'] = $hostname;
    $overrides['server']['protocol'] = $request->getScheme();
    $overrides['server']['port'] = $request->getPort();
    $overrides['server']['path'] = $request->getBasePath() . '/cas-mock-server';
    $overrides['server']['verify'] = CasHelper::CA_NONE;
    $overrides['server']['version'] = '3.0';

    return $overrides;
  }

  /**
   * Returns whether or not the given hostname is resolvable.
   *
   * @param string $hostname
   *   The hostname to check.
   *
   * @return bool
   *   TRUE if the host is resolvable.
   */
  protected static function isResolvable($hostname) {
    // Lifted from Drush.
    // @see \Drush\Exec\ExecTrait::startBrowser()
    $host_error = gethostbynamel($hostname) === FALSE;
    $ip_error = ip2long($hostname) && gethostbyaddr($hostname) === $hostname;

    return !($host_error || $ip_error);
  }

}
