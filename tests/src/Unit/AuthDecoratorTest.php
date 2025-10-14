<?php

namespace Drupal\Tests\mail_login\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\mail_login\AuthDecorator;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserAuthenticationInterface;
use Drupal\user\UserInterface;

/**
 * Tests for the AuthDecorator class.
 *
 * @group mail_login
 */
class AuthDecoratorTest extends UnitTestCase {

  /**
   * The mocked user authentication service.
   *
   * @var \Drupal\user\UserAuthInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $userAuth;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $connection;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The mocked config object.
   *
   * @var \Drupal\Core\Config\Config|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $config;

  /**
   * The mocked messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $messenger;

  /**
   * The mocked user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $userStorage;

  /**
   * The AuthDecorator instance under test.
   *
   * @var \Drupal\mail_login\AuthDecorator
   */
  protected $authDecorator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create mocks for all dependencies.
    $this->userAuth = $this->createMock(UserAuthInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->connection = $this->createMock(Connection::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->config = $this->createMock(Config::class);
    $this->messenger = $this->createMock(MessengerInterface::class);
    $this->userStorage = $this->createMock(EntityStorageInterface::class);

    // Configure config factory to return our config mock.
    $this->configFactory->expects($this->any())
      ->method('get')
      ->with('mail_login.settings')
      ->willReturn($this->config);

    // Configure entity type manager to return user storage mock.
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('user')
      ->willReturn($this->userStorage);

    // Create the AuthDecorator instance with mocked dependencies.
    $this->authDecorator = new AuthDecorator(
      $this->userAuth,
      $this->entityTypeManager,
      $this->connection,
      $this->configFactory,
      $this->messenger
    );

    // Mock the string translation service to avoid container dependency.
    $string_translation = $this->getStringTranslationStub();
    $this->authDecorator->setStringTranslation($string_translation);
  }

  /**
   * Test that AuthDecorator can be instantiated with mocked dependencies.
   */
  public function testAuthDecoratorInstantiation() {
    $this->assertInstanceOf(AuthDecorator::class, $this->authDecorator);
    $this->assertInstanceOf(UserAuthInterface::class, $this->authDecorator);
    $this->assertInstanceOf(UserAuthenticationInterface::class, $this->authDecorator);
  }

  /**
   * Test lookupAccount with a valid email address.
   */
  public function testLookupAccountWithValidEmail() {
    $email = 'user@example.com';
    $user = $this->createMockUser(123, 'testuser', $email, FALSE);

    // Configure mail_login_enabled = TRUE.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],
        ['mail_login_case_sensitive', TRUE],
        ['mail_login_email_only', FALSE],
      ]);

    // Mock user storage to return our test user for email lookup.
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['mail' => $email])
      ->willReturn([$user]);

    // Call lookupAccount with valid email.
    $result = $this->authDecorator->lookupAccount($email);

    // Assert correct user object is returned.
    $this->assertSame($user, $result);
  }

  /**
   * Test lookupAccount with a valid username (fallback behavior).
   */
  public function testLookupAccountWithValidUsername() {
    $username = 'testuser';
    $user = $this->createMockUser(123, $username, 'user@example.com', FALSE);

    // Configure mail_login_enabled = TRUE, email_only = FALSE.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],
        ['mail_login_case_sensitive', TRUE],
        ['mail_login_email_only', FALSE],
      ]);

    // Mock user storage to return empty array for email lookup (no email match).
    // Then return our test user for username lookup.
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => $username])
      ->willReturn([$user]);

    // Call lookupAccount with username.
    $result = $this->authDecorator->lookupAccount($username);

    // Assert correct user object is returned.
    $this->assertSame($user, $result);
  }

  /**
   * Test lookupAccount in email-only mode rejects username login.
   */
  public function testLookupAccountEmailOnlyModeWithUsername() {
    $username = 'testuser';

    // Configure mail_login_enabled = TRUE, email_only = TRUE.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],
        ['mail_login_case_sensitive', TRUE],
        ['mail_login_email_only', TRUE],
      ]);

    // The username is not a valid email, so no loadByProperties call for email lookup.
    // But the code will still try to load by email first, then hit the email_only check.
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['mail' => $username])
      ->willReturn([]);

    // Expect error message to be displayed via messenger.
    $this->messenger->expects($this->once())
      ->method('addError')
      ->with($this->callback(function($message) {
        return $message instanceof \Drupal\Core\StringTranslation\TranslatableMarkup &&
               strpos((string) $message, 'Login by username has been disabled') !== FALSE;
      }));

    // Call lookupAccount with username.
    $result = $this->authDecorator->lookupAccount($username);

    // Assert FALSE is returned.
    $this->assertFalse($result);
  }

  /**
   * Test lookupAccount when mail login is disabled.
   */
  public function testLookupAccountMailLoginDisabled() {
    $identifier = 'user@example.com';

    // Configure mail_login_enabled = FALSE.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', FALSE],
        ['mail_login_case_sensitive', TRUE],
        ['mail_login_email_only', FALSE],
      ]);

    // User storage should not be called when mail login is disabled.
    $this->userStorage->expects($this->never())
      ->method('loadByProperties');

    // Messenger should not be called.
    $this->messenger->expects($this->never())
      ->method('addError');

    // Call lookupAccount with email.
    $result = $this->authDecorator->lookupAccount($identifier);

    // Assert FALSE is returned (mail login disabled).
    $this->assertFalse($result);
  }

  /**
   * Test lookupAccount with case-sensitive email matching.
   */
  public function testLookupAccountCaseSensitive() {
    $email = 'User@Example.com';
    $user = $this->createMockUser(123, 'testuser', $email, FALSE);

    // Configure mail_login_enabled = TRUE, case_sensitive = TRUE.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],
        ['mail_login_case_sensitive', TRUE],
        ['mail_login_email_only', FALSE],
      ]);

    // Mock user storage to return our test user for exact email match.
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['mail' => $email])
      ->willReturn([$user]);

    // Call lookupAccount with exact case email.
    $result = $this->authDecorator->lookupAccount($email);

    // Assert correct user object is returned.
    $this->assertSame($user, $result);
  }

  /**
   * Test lookupAccount with case-insensitive email matching.
   */
  public function testLookupAccountCaseInsensitive() {
    $email_input = 'USER@EXAMPLE.COM';
    $email_stored = 'user@example.com';
    $user = $this->createMockUser(123, 'testuser', $email_stored, FALSE);

    // Configure mail_login_enabled = TRUE, case_sensitive = FALSE.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],
        ['mail_login_case_sensitive', FALSE],
        ['mail_login_email_only', FALSE],
      ]);

    // Mock user storage to return empty for exact match, then use database query.
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['mail' => $email_input])
      ->willReturn([]);

    // Mock the database query for case-insensitive lookup.
    $query = $this->createMock(\Drupal\Core\Entity\Query\QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('condition')
      ->with('mail', $this->anything(), 'LIKE')
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('execute')
      ->willReturn([123]);

    $this->userStorage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);

    $this->userStorage->expects($this->once())
      ->method('loadMultiple')
      ->with([123])
      ->willReturn([123 => $user]);

    // Mock database escapeLike method.
    $this->connection->expects($this->once())
      ->method('escapeLike')
      ->with($email_input)
      ->willReturn($email_input);

    // Call lookupAccount with different case email.
    $result = $this->authDecorator->lookupAccount($email_input);

    // Assert correct user object is returned.
    $this->assertSame($user, $result);
  }

  /**
   * Test lookupAccount with a blocked user.
   */
  public function testLookupAccountWithBlockedUser() {
    $email = 'blocked@example.com';
    $user = $this->createMockUser(123, 'blockeduser', $email, TRUE);

    // Configure mail_login_enabled = TRUE.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],
        ['mail_login_case_sensitive', TRUE],
        ['mail_login_email_only', FALSE],
      ]);

    // Mock user storage to return our blocked test user.
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['mail' => $email])
      ->willReturn([$user]);

    // Expect error message to be displayed via messenger.
    $this->messenger->expects($this->once())
      ->method('addError')
      ->with($this->callback(function($message) {
        return $message instanceof \Drupal\Core\StringTranslation\TranslatableMarkup &&
               strpos((string) $message, 'The user has not been activated yet or is blocked') !== FALSE;
      }));

    // Call lookupAccount with blocked user email.
    $result = $this->authDecorator->lookupAccount($email);

    // Assert FALSE is returned for blocked user.
    $this->assertFalse($result);
  }

  /**
   * Test authenticate with valid credentials.
   */
  public function testAuthenticateWithValidCredentials() {
    $email = 'user@example.com';
    $password = 'validpassword';
    $user = $this->createMockUser(123, 'testuser', $email, FALSE);

    // Configure mail_login_enabled = TRUE.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],
        ['mail_login_case_sensitive', TRUE],
        ['mail_login_email_only', FALSE],
      ]);

    // Mock user storage to return our test user for email lookup.
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['mail' => $email])
      ->willReturn([$user]);

    // The authenticate() method calls lookupAccount() first, then authenticateAccount().
    // Since our userAuth is UserAuthInterface (not UserAuthenticationInterface),
    // it should call authenticate() on the original service.
    $this->userAuth->expects($this->once())
      ->method('authenticate')
      ->with('testuser', $password)
      ->willReturn(123);

    // Call authenticate with valid credentials.
    $result = $this->authDecorator->authenticate($email, $password);

    // Assert correct user ID is returned.
    $this->assertEquals(123, $result);
  }

  /**
   * Test authenticate with invalid credentials.
   */
  public function testAuthenticateWithInvalidCredentials() {
    $email = 'user@example.com';
    $password = 'wrongpassword';
    $user = $this->createMockUser(123, 'testuser', $email, FALSE);

    // Configure mail_login_enabled = TRUE.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],
        ['mail_login_case_sensitive', TRUE],
        ['mail_login_email_only', FALSE],
      ]);

    // Mock user storage to return our test user for email lookup.
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['mail' => $email])
      ->willReturn([$user]);

    // Mock the original userAuth service to return FALSE for authentication.
    $this->userAuth->expects($this->once())
      ->method('authenticate')
      ->with('testuser', $password)
      ->willReturn(FALSE);

    // Call authenticate with invalid credentials.
    $result = $this->authDecorator->authenticate($email, $password);

    // Assert FALSE is returned.
    $this->assertFalse($result);
  }

  /**
   * Test authenticateAccount method with UserAuthenticationInterface.
   */
  public function testAuthenticateAccountMethod() {
    $password = 'testpassword';
    $user = $this->createMockUser(123, 'testuser', 'user@example.com', FALSE);

    // Mock the original userAuth service as UserAuthenticationInterface.
    $userAuthInterface = $this->createMock(UserAuthenticationInterface::class);
    $userAuthInterface->expects($this->once())
      ->method('authenticateAccount')
      ->with($user, $password)
      ->willReturn(TRUE);

    // Create AuthDecorator with UserAuthenticationInterface mock.
    $authDecorator = new AuthDecorator(
      $userAuthInterface,
      $this->entityTypeManager,
      $this->connection,
      $this->configFactory,
      $this->messenger
    );

    // Mock the string translation service.
    $string_translation = $this->getStringTranslationStub();
    $authDecorator->setStringTranslation($string_translation);

    // Call authenticateAccount.
    $result = $authDecorator->authenticateAccount($user, $password);

    // Assert TRUE is returned.
    $this->assertTrue($result);
  }

  /**
   * Test authenticateAccount method with legacy UserAuthInterface.
   */
  public function testAuthenticateAccountMethodLegacy() {
    $password = 'testpassword';
    $user = $this->createMockUser(123, 'testuser', 'user@example.com', FALSE);

    // Mock the original userAuth service as legacy UserAuthInterface only.
    $legacyUserAuth = $this->createMock(UserAuthInterface::class);
    $legacyUserAuth->expects($this->once())
      ->method('authenticate')
      ->with('testuser', $password)
      ->willReturn(123);

    // Create AuthDecorator with legacy UserAuthInterface mock.
    $authDecorator = new AuthDecorator(
      $legacyUserAuth,
      $this->entityTypeManager,
      $this->connection,
      $this->configFactory,
      $this->messenger
    );

    // Mock the string translation service.
    $string_translation = $this->getStringTranslationStub();
    $authDecorator->setStringTranslation($string_translation);

    // Call authenticateAccount.
    $result = $authDecorator->authenticateAccount($user, $password);

    // Assert TRUE is returned (non-zero user ID converted to boolean).
    $this->assertTrue($result);
  }

  /**
   * Helper method to create a mock user object.
   *
   * @param int $uid
   *   The user ID.
   * @param string $username
   *   The username.
   * @param string $email
   *   The email address.
   * @param bool $blocked
   *   Whether the user is blocked.
   *
   * @return \Drupal\user\UserInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked user object.
   */
  protected function createMockUser($uid, $username, $email, $blocked = FALSE) {
    $user = $this->createMock(UserInterface::class);
    
    $user->expects($this->any())
      ->method('id')
      ->willReturn($uid);
    
    $user->expects($this->any())
      ->method('getAccountName')
      ->willReturn($username);
    
    $user->expects($this->any())
      ->method('getEmail')
      ->willReturn($email);
    
    $user->expects($this->any())
      ->method('isBlocked')
      ->willReturn($blocked);
    
    return $user;
  }

}
