<?php

declare(strict_types = 1);

namespace Drupal\cas_mock_server\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Provides an event class for the CasMockServerEvents::RESPONSE_ALTER event.
 *
 * @see \Drupal\cas_mock_server\Event\CasMockServerEvents::RESPONSE_ALTER
 */
class CasMockServerResponseAlterEvent extends Event {

  /**
   * The CAS response XML DOM object.
   *
   * @var \DOMDocument
   */
  protected $dom;

  /**
   * The CAS username.
   *
   * @var string
   */
  protected $username;

  /**
   * The user data.
   *
   * @var array
   */
  protected $userData;

  /**
   * Constructs a new event class instance.
   *
   * @param \DOMDocument $dom
   *   The CAS response XML DOM to be altered.
   * @param string $username
   *   The CAS username.
   * @param array $user_data
   *   User data.
   */
  public function __construct(\DOMDocument $dom, string $username, array $user_data = []) {
    $this->dom = $dom;
    $this->username = $username;
    $this->userData = $user_data;
  }

  /**
   * Sets the CAS response XML DOM object.
   *
   * @param \DOMDocument $dom
   *   The CAS response XML DOM object.
   *
   * @return $this
   */
  public function setDom(\DOMDocument $dom): self {
    $this->dom = $dom;
    return $this;
  }

  /**
   * Returns the CAS response XML DOM object.
   *
   * @return \DOMDocument
   *   The CAS response XML DOM object.
   */
  public function getDom(): \DOMDocument {
    return $this->dom;
  }

  /**
   * Returns the CAS username.
   *
   * @return string
   *   The CAS username.
   */
  public function getUsername(): string {
    return $this->username;
  }

  /**
   * Returns the CAS user data.
   *
   * @return array
   *   The CAS user data.
   */
  public function getUserData(): array {
    return $this->userData;
  }

}
