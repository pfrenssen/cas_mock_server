<?php

declare(strict_types = 1);

namespace Drupal\cas_mock_server\Controller;

use Drupal\cas_mock_server\UserManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * Constructs a CasMockServerController.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\cas_mock_server\UserManagerInterface $userManager
   *   The CAS mock user manager.
   */
  public function __construct(RequestStack $requestStack, UserManagerInterface $userManager) {
    $this->requestStack = $requestStack;
    $this->userManager = $userManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('cas_mock_server.user_manager')
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
    $username = $user_data['username'];

    // Compile the attributes to include in the XML response.
    $attributes = "<cas:attributes>\n";
    foreach ($user_data as $attribute => $value) {
      if (in_array($attribute, ['username', 'service_ticket'])) {
        continue;
      }
      $attributes .= "<cas:$attribute>$value</cas:$attribute>\n";
    }
    $attributes .= "</cas:attributes>\n";

    // Generate the response.
    $response = <<<XML
<cas:serviceResponse xmlns:cas='http://www.yale.edu/tp/cas'>
  <cas:authenticationSuccess>
    <cas:user>$username</cas:user>
    $attributes
  </cas:authenticationSuccess>
</cas:serviceResponse>
XML;
    return Response::create($response, 200);
  }

}
