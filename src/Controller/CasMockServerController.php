<?php

declare(strict_types = 1);

namespace Drupal\cas_mock_server\Controller;

use Drupal\cas_mock_server\Event\CasMockServerEvents;
use Drupal\cas_mock_server\Event\CasMockServerResponseAlterEvent;
use Drupal\cas_mock_server\UserManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for CAS mock server routes.
 */
class CasMockServerController extends ControllerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The CAS mock user manager.
   *
   * @var \Drupal\cas_mock_server\UserManagerInterface
   */
  protected $userManager;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a CasMockServerController.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\cas_mock_server\UserManagerInterface $userManager
   *   The CAS mock user manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(RequestStack $requestStack, UserManagerInterface $userManager, EventDispatcherInterface $event_dispatcher) {
    $this->requestStack = $requestStack;
    $this->userManager = $userManager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('cas_mock_server.user_manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Validates a service ticket.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when a service ticket is missing or is invalid.
   */
  public function validate(): Response {
    $request = $this->requestStack->getCurrentRequest();

    // If there is no service ticket we can not validate anything.
    if (!$request->query->has('ticket')) {
      throw new NotFoundHttpException();
    }

    // Locate the user that issued the given ticket.
    $ticket = $request->query->get('ticket');
    if (!$user_data = $this->userManager->getUserByServiceTicket($ticket)) {
      throw new NotFoundHttpException();
    }

    return Response::create($this->getContent($user_data), 200);
  }

  /**
   * Builds the CAS response XML content.
   *
   * @param array $user_data
   *   The user data.
   *
   * @return string
   *   The XML blob.
   */
  protected function getContent(array $user_data = []): string {
    $username = $user_data['username'];
    unset($user_data['username'], $user_data['service_ticket']);

    $dom = new \DOMDocument();
    $dom->preserveWhiteSpace = FALSE;
    $dom->encoding = "utf-8";

    $response = $dom->createElementNS('http://www.yale.edu/tp/cas', 'cas:serviceResponse');
    $authentication_success = $dom->createElement('cas:authenticationSuccess');
    $user = $dom->createElement('cas:user');
    $user->textContent = $username;
    $authentication_success->appendChild($user);

    if ($user_data) {
      $attributes = $dom->createElement('cas:attributes');
      foreach ($user_data as $key => $value) {
        $attribute = $dom->createElement("cas:$key");
        $attribute->textContent = $value;
        $attributes->appendChild($attribute);
      }
      $authentication_success->appendChild($attributes);
    }

    $response->appendChild($authentication_success);
    $dom->appendChild($response);

    // The default DOM is built. There are CAS Server implementations that are
    // responding with a different XML structure. Allow third-party modules to
    // alter the XML document object model (DOM).
    $response_alter_event = new CasMockServerResponseAlterEvent($dom, $username, $user_data);
    $this->eventDispatcher->dispatch(CasMockServerEvents::RESPONSE_ALTER, $response_alter_event);

    return $response_alter_event->getDom()->saveXML();
  }

}
