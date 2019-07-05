<?php

namespace Drupal\Tests\cas_mock_server\Kernel;

use Drupal\cas_mock_server\Cache\Context\IsCasMockServerActiveCacheContext;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for the cache context that varies by the mock server state.
 *
 * @group cas_mock_server
 * @coversDefaultClass \Drupal\cas_mock_server\Cache\Context\IsCasMockServerActiveCacheContext
 */
class IsCasMockServerActiveCacheContextTest extends KernelTestBase {

  /**
   * The cache context being tested.
   *
   * @var \Drupal\cas_mock_server\Cache\Context\IsCasMockServerActiveCacheContext
   */
  protected $cacheContext;

  /**
   * The server manager service.
   *
   * @var \Drupal\cas_mock_server\ServerManagerInterface
   */
  protected $serverManager;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['cas_mock_server'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->cacheContext = $this->container->get('cache_context.cas_mock_server_is_active');
    $this->serverManager = $this->container->get('cas_mock_server.server_manager');
  }

  /**
   * Tests that the correct context is returned.
   *
   * @covers ::getContext
   */
  public function testGetContext() {
    // Check that the cache context returns the correct value when the mock
    // server is active.
    $this->serverManager->start();
    $this->assertEquals(IsCasMockServerActiveCacheContext::SERVER_ACTIVE, $this->cacheContext->getContext());

    // Check the value if the server is inactive.
    $this->serverManager->stop();
    $this->assertEquals(IsCasMockServerActiveCacheContext::SERVER_INACTIVE, $this->cacheContext->getContext());
  }

}
