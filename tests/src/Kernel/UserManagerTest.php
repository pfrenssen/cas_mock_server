<?php

declare(strict_types = 1);

namespace Drupal\Tests\cas_mock_server\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for the default implementation of the UserManager service.
 *
 * @group cas_mock_server
 * @coversDefaultClass \Drupal\cas_mock_server\UserManager
 */
class UserManagerTest extends KernelTestBase {

  /**
   * The user manager service. This is the system under test.
   *
   * @var \Drupal\cas_mock_server\UserManagerInterface
   */
  protected $userManager;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['cas_mock_server'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->userManager = $this->container->get('cas_mock_server.user_manager');
  }

  /**
   * @covers ::addUser
   */
  public function testAddUser(): void {
    // Initially there are no users present.
    $this->assertUserCount(0);

    // Add a user.
    $user = [
      'username' => 'Teppic',
      'email' => 'king@gov.djelibeybi',
      'password' => 'Ptraci',
      'first_name' => 'Pteppicymon',
      'last_name' => 'XXVIII',
      'position' => 'king',
    ];
    $this->userManager->addUser($user);

    // Now there should be 1 user.
    $this->assertUserCount(1);

    // Check that the user data is correctly stored.
    $this->assertUser('Teppic', $user);

    // Adding a user with the same username should overwrite the existing user
    // with the new data.
    $user['position'] = 'Lord of the Heavens, Charioteer of the Wagon of the Sun, Steersman of the Barque of the Sun, Guardian of the Secret Knowledge, Lord of the Horizon, Keeper of the Way, the Flail of Mercy, the High Born One, the Never Dying King';
    $this->userManager->addUser($user);
    $this->assertUserCount(1);
    $this->assertUser('Teppic', $user);

    // Adding a user with a different username should keep the existing user
    // intact.
    $user2 = [
      'username' => 'Ptaclusp',
      'email' => 'ptaclusp@ptaclusp-associates.djelibeybi',
      'password' => 'pyramids',
      'first_name' => 'Ptaclusp',
      'last_name' => 'I',
      'position' => 'Head of Engineering',
      'company' => 'Ptaclusp Associates',
    ];
    $this->userManager->addUser($user2);
    $this->assertUserCount(2);
    $this->assertUser('Teppic', $user);
    $this->assertUser('Ptaclusp', $user2);

    // When a required attribute is missing an exception should be thrown.
    unset($user2['email']);
    try {
      $this->userManager->addUser($user2);
      $this->fail();
    }
    catch (\InvalidArgumentException $e) {
      // Expected result.
    }
  }

  /**
   * @covers ::addUsers
   */
  public function testAddUsers(): void {
    // Initially there are no users present.
    $this->assertUserCount(0);

    // Add 2 users.
    $users = [
      [
        'username' => 'Teppic',
        'email' => 'king@gov.djelibeybi',
        'password' => 'Ptraci',
        'first_name' => 'Pteppicymon',
        'last_name' => 'XXVIII',
        'position' => 'king',
      ],
      [
        'username' => 'Ptaclusp',
        'email' => 'ptaclusp@ptaclusp-associates.djelibeybi',
        'password' => 'pyramids',
        'first_name' => 'Ptaclusp',
        'last_name' => 'I',
        'position' => 'Head of Engineering',
        'company' => 'Ptaclusp Associates',
      ],
    ];
    $this->userManager->addUsers($users);

    // Now there should be 2 users.
    $this->assertUserCount(2);

    // Check that the user data is correctly stored.
    $this->assertUser('Teppic', $users[0]);
    $this->assertUser('Ptaclusp', $users[1]);

    // Add 2 more users, one of which is an existing user with new data. The new
    // user should be added and the existing user should be updated.
    $users2 = [
      [
        'username' => 'Ptaclusp',
        'email' => 'ptaclusp@ptaclusp-associates.djelibeybi',
        'password' => 'time loop',
        'first_name' => 'Ptaclusp',
        'last_name' => 'I',
        'position' => 'Head of Engineering',
        'company' => 'Ptaclusp Associates',
      ],
      [
        'username' => 'Sessifret',
        'email' => 'sessifret@gods.djelibeybi',
        'password' => 'zenith',
        'position' => 'Goddess of the Afternoon',
      ]
    ];
    $this->userManager->addUsers($users2);
    $this->assertUserCount(3);

    // Check that the user data is correctly stored.
    $this->assertUser('Teppic', $users[0]);
    $this->assertUser('Ptaclusp', $users2[0]);
    $this->assertUser('Sessifret', $users2[1]);

    // When a required attribute is missing an exception should be thrown.
    unset($users2[0]['username']);
    try {
      $this->userManager->addUsers($users2);
      $this->fail();
    }
    catch (\InvalidArgumentException $e) {
      // Expected result.
    }
  }

  /**
   * @covers ::getUser
   * @covers ::getUsers
   * @covers ::getUsersByAttributes
   * @covers ::setUsers
   * @covers ::deleteUsers
   */
  public function testUserGettersSetters(): void {
    // Create a number of users.
    $users = [
      [
        'username' => 'esk',
        'email' => 'eskarina.smith@badass.lancre',
        'password' => 'cunningman',
      ],
      [
        'username' => 'M. Ridcully',
        'email' => 'mustrum.ridcully@unseen-university.edu',
        'password' => 'paintball',
        'position' => 'D.Thau., D.M., D.S., D.Mn., D.G., D.D., D.C.L., D.M. Phil., D.M.S., D.C.M., D.W., B.El.L., Archchancellor',
        'first_name' => 'Mustrum',
        'last_name' => 'Ridcully',
      ],
      [
        'username' => 'hridcully',
        'email' => 'hughnon.ridcully@unseen-university.edu',
        'password' => 'BlindIo',
        'first_name' => 'Hughnon',
        'last_name' => 'Ridcully',
      ],
      [
        'username' => 'stibbo',
        'email' => 'ponder.stibbons@unseen-university.edu',
        'password' => 'splitting_thaum_012',
      ],
    ];
    $this->userManager->setUsers($users);

    // There should be 4 users.
    $this->assertUserCount(4);
    $this->assertUser('esk', $users[0]);
    $this->assertUser('M. Ridcully', $users[1]);
    $this->assertUser('hridcully', $users[2]);
    $this->assertUser('stibbo', $users[3]);

    // Check that we can retrieve an individual user.
    $user = $this->userManager->getUser('hridcully');
    $this->assertSame($users[2], $user);

    // Check that we can retrieve multiple users.
    $actual_users = $this->userManager->getUsers(['esk', 'stibbo']);
    $this->assertCount(2, $actual_users);
    $this->assertSame($users[0], $actual_users['esk']);
    $this->assertSame($users[3], $actual_users['stibbo']);

    // Calling ::getUsers() without an argument should return all users.
    $actual_users = $this->userManager->getUsers();
    $this->assertCount(4, $actual_users);
    $this->assertSame($users[0], $actual_users['esk']);
    $this->assertSame($users[1], $actual_users['M. Ridcully']);
    $this->assertSame($users[2], $actual_users['hridcully']);
    $this->assertSame($users[3], $actual_users['stibbo']);

    // Check that we can retrieve multiple users that have identical attributes.
    $actual_users = $this->userManager->getUsersByAttributes([
      'last_name' => 'Ridcully',
    ]);
    $this->assertCount(2, $actual_users);
    $this->assertSame($users[1], $actual_users['M. Ridcully']);
    $this->assertSame($users[2], $actual_users['hridcully']);

    // Check that we can retrieve a user that matches multiple attributes.
    $actual_users = $this->userManager->getUsersByAttributes([
      'email' => 'eskarina.smith@badass.lancre',
      'password' => 'cunningman',
    ]);
    $this->assertCount(1, $actual_users);
    $this->assertSame($users[0], $actual_users['esk']);

    // Check that we can delete users.
    $this->userManager->deleteUsers(['stibbo', 'hridcully']);
    $this->assertUserCount(2);

    // Try to request a deleted user. This should result in an exception.
    try {
      $this->userManager->getUser('hridcully');
      $this->fail();
    }
    catch (\InvalidArgumentException $e) {
      // Expected result.
    }

    // Try setting the users again. This should overwrite the existing users.
    unset($users[0]);
    unset($users[3]);
    $users[1]['password'] = 'exercise';

    $this->userManager->setUsers($users);
    $this->assertUserCount(2);
    $this->assertUser('M. Ridcully', $users[1]);
    $this->assertUser('hridcully', $users[2]);

    // Calling ::deleteUsers() without argument should delete all users.
    $this->userManager->deleteUsers();
    $this->assertCount(0, $this->userManager->getUsers());
  }

  /**
   * @covers ::assignServiceTicket
   * @covers ::getUserByServiceTicket
   */
  public function testAssignServiceTicket(): void {
    $user = [
      'username' => 'oats',
      'email' => 'mightily.oats@ohulancutash.lancre',
      'password' => 'om',
    ];
    $this->userManager->addUser($user);

    // When no service ticket is assigned, no results should be returned.
    $service_ticket = 'ST-123456789';
    $this->assertNull($this->userManager->getUserByServiceTicket($service_ticket));

    // Assign the ticket, now we should get a result.
    $this->userManager->assignServiceTicket('oats', $service_ticket);
    $this->assertSame($user['username'], $this->userManager->getUserByServiceTicket($service_ticket)['username']);

    // Assigning the same service ticket to a different user should thrown an
    // error.
    $user2 = [
      'username' => 'perdore',
      'email' => 'brother.perdore@nine-day-wonderers.ramtops',
      'password' => 'skund',
    ];
    $this->userManager->addUser($user2);
    try {
      $this->userManager->assignServiceTicket('perdore', $service_ticket);
      $this->fail();
    }
    catch (\InvalidArgumentException $e) {
      // Expected result.
    }
  }

  /**
   * Checks that the user manager is managing the given number of users.
   *
   * @param int $count
   *   The expected user count.
   */
  protected function assertUserCount(int $count): void {
    $this->assertEquals($count, count($this->userManager->getUsers()));
  }

  /**
   * Checks that the user with the given name has the given data.
   *
   * @param string $username
   *   The name of the user to check.
   * @param array $user_data
   *   The user data that is expected to be returned by the user manager.
   */
  protected function assertUser(string $username, array $user_data): void {
    $user = $this->userManager->getUser($username);
    sort($user);
    sort($user_data);
    $this->assertSame($user_data, $user);
  }
}
