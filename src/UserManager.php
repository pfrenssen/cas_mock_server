<?php

declare(strict_types = 1);

namespace Drupal\cas_mock_server;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;

/**
 * Default implementation of the user manager service for the CAS mock server.
 *
 * This implementation uses an expirable key value store for the user data. It
 * is intended only for testing purposes.
 * - User data is not persisted. The data will be erased automatically after a
 *   time limit is reached.
 * - Passwords are stored without encryption.
 * - This implementation is not intended to scale and should only be used with a
 *   limited number of test users.
 */
class UserManager implements UserManagerInterface {

  /**
   * The cache backend serving as storage for the users.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface
   */
  protected $keyValueFactory;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an UserManager service.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $keyValueFactory
   *   The factory for key value stores, one of which will serve as storage for
   *   the users.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(KeyValueExpirableFactoryInterface $keyValueFactory, ConfigFactoryInterface $configFactory) {
    $this->keyValueFactory = $keyValueFactory;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function addUser(array $user): void {
    $this->addUsers([$user]);
  }

  /**
   * {@inheritdoc}
   */
  public function addUsers(array $users): void {
    $users = $this->validateUsers($users);
    $this->setUsers($users + $this->getUsers());
  }

  /**
   * {@inheritdoc}
   */
  public function getUser(string $username): array {
    $users = $this->getUsers([$username]);
    if (count($users) === 1) {
      return reset($users);
    }
    throw new \InvalidArgumentException("User '$username' does not exist.");
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers(array $usernames = NULL): array {
    $users = $this->loadUsers();

    if (!empty($usernames)) {
      $users = array_intersect_key($users, array_flip($usernames));
    }

    return $users;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsersByAttributes(array $attributes): array {
    return array_filter($this->getUsers(), function (array $user) use ($attributes): bool {
      return empty(array_diff_assoc($attributes, $user));
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getUserByServiceTicket(string $ticket): ?array {
    $users = $this->getUsersByAttributes(['service_ticket' => $ticket]);
    if (count($users) === 1) {
      return reset($users);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setUsers(array $users): void {
    $users = $this->validateUsers($users);
    $this->getStorage()->set('users', $users);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteUsers(array $usernames = NULL): void {
    if (empty($usernames)) {
      $this->getStorage()->delete('users');
    }
    else {
      $users = $this->getUsers();
      $users = array_diff_key($users, array_flip($usernames));
      $this->setUsers($users);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function assignServiceTicket(string $username, string $ticket): void {
    // Check that the service ticket is not yet assigned to a different user.
    $user = $this->getUserByServiceTicket($ticket);
    if (!empty($user) && $user['username'] !== $username) {
      throw new \InvalidArgumentException('The service ticket is already assigned to ' . $user['username']);
    }

    $user = $this->getUser($username);
    $user['service_ticket'] = $ticket;
    $this->addUser($user);
  }

  /**
   * Validates the given users.
   *
   * @param array $users
   *   The users to validate.
   *
   * @return array
   *   The validated users, keyed by username.
   *
   * @throws \InvalidArgumentException
   *   Thrown when one or more of the passed in users are not valid.
   */
  protected function validateUsers(array $users): array {
    if (!$this->areUsersValid($users)) {
      throw new \InvalidArgumentException('Invalid user data');
    }

    // Make sure the array of users is keyed by username.
    return array_combine(array_column($users, 'username'), array_values($users));
  }

  /**
   * Returns whether or not the passed in users are valid.
   *
   * @param array $users
   *   The users to validate.
   *
   * @return bool
   *   TRUE if the users valid, FALSE otherwise.
   */
  protected function areUsersValid(array $users): bool {
    foreach ($users as $user) {
      if (!$this->isUserValid($user)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Returns whether or not the passed in user is valid.
   *
   * This will check that the user contains all required attributes and consists
   * of an associative array of scalar values, with keys consisting of string
   * values.
   *
   * @param array $user_data
   *   The user data to validate.
   *
   * @return bool
   *   TRUE if the user is valid, FALSE otherwise.
   */
  protected function isUserValid(array $user_data): bool {
    // Check that all required attributes are present.
    $missing_required_attributes = array_diff(UserManagerInterface::REQUIRED_ATTRIBUTES, array_keys($user_data));
    if (!empty($missing_required_attributes)) {
      return FALSE;
    }

    // Check that the data consists only of scalar values, and that all array
    // keys are strings.
    foreach ($user_data as $key => $value) {
      if (!is_scalar($value) || !is_string($key)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Retrieves the users from the cache backend.
   *
   * @return array
   *   The list of users, keyed by username.
   */
  protected function loadUsers(): array {
    $users = $this->getStorage()->get('users');
    return $users ?? [];
  }

  /**
   * Returns the user storage.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   *   The key value store.
   */
  protected function getStorage(): KeyValueStoreExpirableInterface {
    return $this->keyValueFactory->get('cas_mock_server');
  }

  /**
   * Returns the expiration time for mock users.
   *
   * @return int
   *   The expiration time.
   */
  protected function getExpirationTime(): int {
    return $this->configFactory->get('cas_mock_server.settings')->get('user.expire');
  }

}
