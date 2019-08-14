<?php

declare(strict_types = 1);

namespace Drupal\cas_mock_server\Event;

/**
 * Provides CAS mock server event identifiers.
 */
final class CasMockServerEvents {

  /**
   * Event identifier for the CasMockServerResponseAlterEvent event.
   *
   * @var string
   *
   * @see \Drupal\cas_mock_server\Event\CasMockServerResponseAlterEvent
   */
  const RESPONSE_ALTER = 'cas_mock_server.response_alter';

}
