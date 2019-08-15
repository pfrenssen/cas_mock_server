<?php

declare(strict_types = 1);

namespace Drupal\cas_mock_server_test\EventSubscriber;

use Drupal\cas_mock_server\Event\CasMockServerEvents;
use Drupal\cas_mock_server\Event\CasMockServerResponseAlterEvent;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to CasMockServerEvents::RESPONSE_ALTER event.
 */
class CasMockServerTestSubscriber implements EventSubscriberInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a CasMockServerTestSubscriber.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CasMockServerEvents::RESPONSE_ALTER => 'alterResponse',
    ];
  }

  /**
   * Alters the service response if a test demands it.
   *
   * @param \Drupal\cas_mock_server\Event\CasMockServerResponseAlterEvent $event
   *   The event object.
   */
  public function alterResponse(CasMockServerResponseAlterEvent $event): void {
    // Only alter the response if a test demands this.
    if (!$this->state->get('cas_mock_server_test.alter_response', FALSE)) {
      return;
    }

    // Append a custom element to the response.
    $dom = $event->getDom();
    $element = $dom->createElement("cas:custom");
    $element->textContent = 'altered';
    $dom->appendChild($element);
  }

}
