<?php

namespace Drupal\cas_mock_server;

use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Default implementation of the user manager service for the CAS mock server.
 *
 * This implementation uses the Cache API to store the user data. It is intended
 * only for testing purposes.
 * - User data is not persisted. A simple cache clear will cause all data to be
 *   lost.
 * - Passwords are stored without encryption.
 * - This implementation is not intended to scale and should only be used with a
 *   limited number of test users.
 */
class UserManager implements UserManagerInterface {

  /**
   * The cache backend serving as storage for the users.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $storage;

  /**
   * Constructs an UserManager object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $storage
   *   The cache backend serving as storage for the users.
   */
  public function __construct(CacheBackendInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public function addUser(array $user) {
    $this->addUsers([$user]);
  }

  /**
   * {@inheritdoc}
   */
  public function addUsers(array $users) {
    $users = $this->validateUsers($users);
    $this->setUsers($users + $this->getUsers());
  }

  /**
   * {@inheritdoc}
   */
  public function getUser($username) {
    $users = $this->getUsers([$username]);
    if (count($users) === 1) {
      return reset($users);
    }
    throw new \InvalidArgumentException("User '$username' does not exist.");
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers(array $usernames = NULL) {
    $users = $this->loadUsers();

    if (!empty($usernames)) {
      $users = array_intersect_key($users, array_flip($usernames));
    }

    return $users;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsersByAttributes(array $attributes) {
    return array_filter($this->getUsers(), function (array $user) use ($attributes) {
      return empty(array_diff_assoc($attributes, $user));
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getUserByServiceTicket($ticket) {
    $users = $this->getUsersByAttributes(['service_ticket' => $ticket]);
    if (count($users) === 1) {
      return reset($users);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setUsers(array $users) {
    $users = $this->validateUsers($users);
    $this->storage->set('users', $users);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteUsers(array $usernames = NULL) {
    if (empty($usernames)) {
      $this->storage->delete('users');
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
  public function assignServiceTicket($username, $ticket) {
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
  protected function validateUsers(array $users) {
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
  protected function areUsersValid(array $users) {
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
  protected function isUserValid(array $user_data) {
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
  protected function loadUsers() {
    $cache = $this->storage->get('users');
    if ($cache) {
      return $cache->data;
    }
    return [];
  }

}
