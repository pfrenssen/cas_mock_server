<?php

declare(strict_types = 1);

namespace Drupal\cas_mock_server;

use Drupal\cas_mock_server\Exception\CasMockServerException;
use Drupal\cas_mock_server\Exception\UnresolvableHostException;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service providing methods to start and stop the mock server.
 */
class ServerManager implements ServerManagerInterface {

  /**
   * The key that identifies the backed up CAS config in the state storage.
   */
  const STATE_KEY_BACKUP = 'cas_mock_server.cas_config_backup';

  /**
   * The key that identifies the state of the mock server in the state storage.
   */
  const STATE_KEY_SERVER_STATE = 'cas_mock_server.state';

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
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a ServerManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(ConfigFactoryInterface $configFactory, StateInterface $state, RequestStack $requestStack) {
    $this->configFactory = $configFactory;
    $this->state = $state;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public function start(): void {
    if ($this->isServerActive()) {
      return;
    }

    // Throw an error if the current request doesn't contain a resolvable
    // hostname. This may happen if we are invoked from the command line.
    $request = $this->requestStack->getCurrentRequest();
    if (!$this->isResolvable($request->getHost())) {
      throw new UnresolvableHostException();
    }

    // Keep track of the original CAS configuration before making changes.
    $this->backupCasConfig();

    // Override the path to the CAS server with our mock server.
    $request = $this->requestStack->getCurrentRequest();
    $scheme = $request->getScheme();
    $host = $request->getHost();
    var_dump($scheme);
    $this->getCasConfig(TRUE);
    $this->state->set(self::STATE_KEY_SERVER_STATE, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function stop(): void {
    if (!$this->isServerActive()) {
      return;
    }

    $this->restoreCasConfig();
    $this->state->set(self::STATE_KEY_SERVER_STATE, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isServerActive(): bool {
    return $this->state->get(self::STATE_KEY_SERVER_STATE, FALSE);
  }

  /**
   * Creates a backup of the original CAS configuration.
   *
   * @throws \Drupal\cas_mock_server\Exception\CasMockServerException
   *   Thrown when a backup already exists.
   */
  protected function backupCasConfig(): void {
    // @todo Instead of overwriting the config, override it.
    // @ref ConfigFactoryOverrideInterface
    // Throw an exception if a backup of the original configuration already
    // exists, to avoid overwriting the backed up data with the data from the
    // mocked server.
    if ($this->state->get(self::STATE_KEY_BACKUP)) {
      throw new CasMockServerException('Backed up CAS configuration already exists.');
    }

    $cas_config = $this->getCasConfig()->getRawData();
    $this->state->set(self::STATE_KEY_BACKUP, $cas_config);
  }

  /**
   * Restores the original CAS configuration.
   *
   * @throws \Drupal\cas_mock_server\Exception\CasMockServerException
   *   Thrown when no backup of the original CAS configuration exists.
   */
  protected function restoreCasConfig(): void {
    $original_data = $this->state->get(self::STATE_KEY_BACKUP);
    if (empty($original_data)) {
      throw new CasMockServerException('No backed up CAS configuration exists.');
    }

    $cas_config = $this->getCasConfig(TRUE);
    $cas_config->setData($original_data)->save(TRUE);

    // The data has been restored, delete the backup.
    $this->state->delete(self::STATE_KEY_BACKUP);
  }

  /**
   * Returns the configuration of the CAS module.
   *
   * @param bool $editable
   *   Whether or not an editable version of the configuration should be
   *   returned. Defaults to FALSE.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration.
   */
  protected function getCasConfig(bool $editable = FALSE): Config {
    if ($editable) {
      return $this->configFactory->getEditable('cas.settings');
    }
    return $this->configFactory->get('cas.settings');
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
  protected function isResolvable(string $hostname): bool {
    // Lifted from Drush.
    // @see \Drush\Exec\ExecTrait::startBrowser()
    $hosterror = (gethostbynamel($hostname) === false);
    $iperror = (ip2long($hostname) && gethostbyaddr($hostname) == $hostname);

    return !($hosterror || $iperror);
  }

}
