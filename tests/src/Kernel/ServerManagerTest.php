<?php

declare(strict_types = 1);

namespace Drupal\Tests\cas_mock_server\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for the default implementation of the ServerManager service.
 *
 * @group cas_mock_server
 * @coversDefaultClass \Drupal\cas_mock_server\ServerManager
 */
class ServerManagerTest extends KernelTestBase {

  /**
   * The server manager service. This is the system under test.
   *
   * @var \Drupal\cas_mock_server\ServerManagerInterface
   */
  protected $serverManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['cas_mock_server'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->serverManager = $this->container->get('cas_mock_server.server_manager');
  }

  /**
   * Tests starting and stopping of the mock server.
   *
   * @covers ::start
   * @covers ::stop
   * @covers ::isServerActive
   */
  public function testStartStop(): void {
    // When the module is initially enabled the server should be inactive.
    $this->assertFalse($this->serverManager->isServerActive());

    // Start the server. Now the server should be active.
    $this->serverManager->start();
    $this->assertTrue($this->serverManager->isServerActive());

    // When the server is started a second time it should remain active.
    $this->serverManager->start();
    $this->assertTrue($this->serverManager->isServerActive());

    // Stop the server and check it is now inactive.
    $this->serverManager->stop();
    $this->assertFalse($this->serverManager->isServerActive());

    // When an inactive server is stopped again it should remain inactive.
    $this->serverManager->stop();
    $this->assertFalse($this->serverManager->isServerActive());
  }

}
