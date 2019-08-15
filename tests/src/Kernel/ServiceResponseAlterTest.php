<?php

declare(strict_types = 1);

namespace Drupal\Tests\cas_mock_server\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests that modules can alter the responses returned by the mock server.
 *
 * @group cas_mock_server
 */
class ServiceResponseAlterTest extends KernelTestBase {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The CAS mock user manager.
   *
   * @var \Drupal\cas_mock_server\UserManagerInterface
   */
  protected $userManager;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'cas',
    'cas_mock_server',
    'cas_mock_server_test',
    'externalauth',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['key_value_expire']);
    $this->installConfig(['cas', 'cas_mock_server']);

    $this->userManager = $this->container->get('cas_mock_server.user_manager');
    $this->state = $this->container->get('state');
  }

  /**
   * Tests that modules can alter the service response.
   */
  public function testResponseAlter(): void {
    // Set up a test user with a service ticket.
    $ticket = 'ST-123456789';
    $user_data = [
      'username' => 'sharon',
      'email' => 'sharon@example.com',
      'password' => 'hunter2',
    ];
    $this->userManager->addUser($user_data);
    $this->userManager->assignServiceTicket('sharon', $ticket);

    // Enable altering of the response by the test module.
    $this->state->set('cas_mock_server_test.alter_response', TRUE);

    // Request to validate the ticket.
    $http_kernel = \Drupal::service('http_kernel');
    $request = Request::create(Url::fromRoute('cas_mock_server.validate', [], [
      'query' => [
        'ticket' => $ticket,
      ],
    ])->toString(TRUE)->getGeneratedUrl());

    // Check that the response has a custom element altered in.
    // @see \Drupal\cas_mock_server_test\EventSubscriber\CasMockServerTestSubscriber::alterResponse()
    $response = $http_kernel->handle($request);
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertContains('<cas:custom>altered</cas:custom>', $response->getContent());
  }

}
