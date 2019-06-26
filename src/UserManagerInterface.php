<?php

declare(strict_types = 1);

namespace Drupal\cas_mock_server;

/**
 * Interface for services that manage users for the CAS mock server.
 *
 * A user manager service is responsible for storing and retrieving mock users
 * that are used by the mock authentication service.
 */
interface UserManagerInterface {

  /**
   * A list of CAS user attributes that are required.
   */
  const REQUIRED_ATTRIBUTES = ['username', 'email', 'password'];

  /**
   * Adds the given user to the storage.
   *
   * If a user already exists with the given username, it will be overwritten.
   *
   * @param array $user
   *   An array of user data.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the passed in user is missing a required attribute.
   */
  public function addUser(array $user):  void;

  /**
   * Adds the given users to the list of existing users.
   *
   * @param array $users
   *   An array of user data. Each value an associative array keyed by CAS
   *   attribute name.
   *
   * @throws \InvalidArgumentException
   *   Thrown when one of the passed in users is missing a required attribute.
   */
  public function addUsers(array $users):  void;

  /**
   * Returns the CAS user with the given name.
   *
   * @param string $username
   *   The name of the user to return.
   *
   * @return array
   *   The user data.
   *
   * @throws \InvalidArgumentException
   *   Thrown if no user exists with the given user name.
   */
  public function getUser(string $username): array;

  /**
   * Returns the CAS users with the given names.
   *
   * @param array $usernames
   *   Optional array of usernames to return. If omitted all users will be'
   *   returned.
   *
   * @return array[]
   *   The users.
   */
  public function getUsers(array $usernames = NULL): array;

  /**
   * Returns the users that match the given attributes.
   *
   * @param array $attributes
   *   The attributes to search for.
   *
   * @return array[]
   *   An array of CAS user data, keyed by username.
   */
  public function getUsersByAttributes(array $attributes): array;

  /**
   * Stores the given users in the mock CAS server storage.
   *
   * This will discard all existing users.
   *
   * @param array $users
   *   An array of user data. Each value an associative array keyed by CAS
   *   attribute name.
   *
   * @throws \InvalidArgumentException
   *   Thrown when one of the passed in users is missing a required attribute.
   */
  public function setUsers(array $users): void;

  /**
   * Deletes the users with the given usernames.
   *
   * @param string[]|null $usernames
   *   An array of usernames to delete. If left empty all users will be deleted.
   */
  public function deleteUsers(array $usernames = NULL): void;

  /**
   * Assigns the given service ticket to the given user.
   *
   * @param string $username
   *   The name of the user to assign the ticket to.
   * @param string $ticket
   *   The service ticket.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the user does not exist, or if the service ticket is already
   *   assigned to a different user.
   */
  public function assignServiceTicket(string $username, string $ticket): void;

  /**
   * Returns the user that corresponds to the given service ticket.
   *
   * @param string $ticket
   *   The service ticket.
   *
   * @return array|null
   *   The user data, or NULL if no user is found for the given service ticket.
   */
  public function getUserByServiceTicket(string $ticket): ?array;

}
