<?php

declare(strict_types = 1);

namespace Drupal\Tests\cas_mock_server\Kernel;

use Drupal\cas_mock_server\ServiceTicketHelper;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for the default implementation of the ServerManager service.
 *
 * @group cas_mock_server
 * @coversDefaultClass \Drupal\cas_mock_server\ServiceTicketHelper
 */
class ServiceTicketHelperTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['cas_mock_server'];

  /**
   * Tests that a valid service ticket can be generated.
   *
   * @covers ::generateServiceTicket
   */
  public function testGenerateServiceTicket(): void {
    // A service ticket must start with "ST-".
    // @see https://apereo.github.io/cas/5.0.x/protocol/CAS-Protocol-Specification.html#311-service-ticket-properties
    $service_ticket = ServiceTicketHelper::generateServiceTicket();
    $this->assertTrue(strpos($service_ticket, 'ST-') === 0);
  }

}
