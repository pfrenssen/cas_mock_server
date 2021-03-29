<?php

declare(strict_types = 1);

namespace Drupal\Tests\cas_mock_server\Kernel;

use Drupal\cas\Service\CasHelper;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests that CAS module config is overridden when the mock server is started.
 *
 * @group cas_mock_server
 * @coversDefaultClass \Drupal\cas_mock_server\Config\CasMockServerConfigOverrider
 */
class CasMockServerConfigOverriderTest extends KernelTestBase {

  /**
   * The server manager service.
   *
   * @var \Drupal\cas_mock_server\ServerManagerInterface
   */
  protected $serverManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'cas',
    'cas_mock_server',
    'externalauth',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['cas']);

    $this->serverManager = $this->container->get('cas_mock_server.server_manager');
    $this->configFactory = $this->container->get('config.factory');

    // In a kernel test a request is not automatically set. Create one with a
    // base URL that is easy to recognize.
    $request = Request::create('http://example.com/');
    $this->container->get('request_stack')->push($request);
  }

  /**
   * Tests that the CAS module config is overridden.
   */
  public function testConfigOverride(): void {
    // When the cas_mock_server module is initially installed the default config
    // from the cas module should be left in its default values.
    $default_config = [
      'protocol' => 'https',
      'hostname' => '',
      'port' => 443,
      'path' => '/',
      'verify' => CasHelper::CA_DEFAULT,
    ];
    $this->assertConfig($default_config);
    $this->assertConfigNotOverridden();

    // Enable the CAS server. This should override the configuration of the CAS
    // module to use the endpoint of the mock server.
    $this->serverManager->start();

    $overridden_config = [
      'protocol' => 'http',
      'hostname' => 'example.com',
      'port' => 80,
      'path' => '/cas-mock-server',
      'verify' => CasHelper::CA_NONE,
    ];
    $this->assertConfig($overridden_config);
    $this->assertConfigOverridden();

    // Disable the mock server. This should restore the configuration of the CAS
    // module to its original non-overridden values.
    $this->serverManager->stop();

    $this->assertConfig($default_config);
    $this->assertConfigNotOverridden();
  }

  /**
   * Checks that the configuration of the CAS module matches the given values.
   *
   * @param array $expected_values
   *   The values to check.
   */
  protected function assertConfig(array $expected_values): void {
    $raw_data = $this->getCasConfig()->getOriginal('server');
    foreach ($expected_values as $key => $expected_value) {
      $this->assertEquals($expected_value, $raw_data[$key]);
    }
  }

  /**
   * Checks that the CAS config is not overridden.
   */
  protected function assertConfigNotOverridden(): void {
    $this->assertFalse($this->getCasConfig()->hasOverrides());
  }

  /**
   * Checks that the CAS config is overridden.
   */
  protected function assertConfigOverridden(): void {
    $this->assertTrue($this->getCasConfig()->hasOverrides());
  }

  /**
   * Returns the CAS config.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The configuration object containing the settings for the CAS module.
   */
  protected function getCasConfig(): ImmutableConfig {
    // The config factory static cache does not support cacheability metadata
    // and might return stale data. We need to invalidate its cache directly.
    // @todo Remove this when the config factory supports cacheability metadata.
    // @see https://www.drupal.org/project/drupal/issues/3063687
    $this->configFactory->reset('cas.settings');

    return $this->configFactory->get('cas.settings');
  }

}
