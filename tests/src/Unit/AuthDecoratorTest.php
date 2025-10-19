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
 * This test class provides comprehensive unit testing for the AuthDecorator
 * service, which handles email-based authentication for the mail_login module.
 * All external dependencies are mocked to ensure isolated testing.
 *
 * Test Coverage:
 * - Email lookup functionality with various email formats
 * - Username fallback behavior when email lookup fails
 * - Case-sensitive and case-insensitive email matching
 * - Email-only mode enforcement
 * - Blocked user handling and error messaging
 * - Configuration-dependent behavior changes
 * - Edge cases and security considerations
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
   *
   * Sets up the test environment with mocked dependencies for AuthDecorator.
   * 
   * This method creates mocks for all external dependencies to ensure complete
   * isolation during unit testing. The mocks are configured with basic
   * expectations that are common across multiple test methods.
   */
  protected function setUp(): void {
    parent::setUp();

    // Create mocks for all dependencies to ensure complete test isolation.
    $this->userAuth = $this->createMock(UserAuthInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->connection = $this->createMock(Connection::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->config = $this->createMock(Config::class);
    $this->messenger = $this->createMock(MessengerInterface::class);
    $this->userStorage = $this->createMock(EntityStorageInterface::class);

    // Configure config factory to return our config mock for mail_login.settings.
    $this->configFactory->expects($this->any())
      ->method('get')
      ->with('mail_login.settings')
      ->willReturn($this->config);

    // Configure entity type manager to return user storage mock for 'user' entities.
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('user')
      ->willReturn($this->userStorage);

    // Create the AuthDecorator instance with all mocked dependencies.
    $this->authDecorator = new AuthDecorator(
      $this->userAuth,
      $this->entityTypeManager,
      $this->connection,
      $this->configFactory,
      $this->messenger
    );

    // Mock the string translation service to avoid container dependency issues.
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
   *
   * This test verifies the core email lookup functionality when:
   * - mail_login is enabled
   * - A valid email format is provided
   * - A matching user exists in the system
   * - The user is not blocked
   *
   * Expected behavior: Returns the matching user object.
   */
  public function testLookupAccountWithValidEmail() {
    $email = 'user@example.com';
    $user = $this->createMockUser(123, 'testuser', $email, FALSE);

    // Configure mail_login settings for standard email lookup behavior.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],        // Enable email login
        ['mail_login_case_sensitive', TRUE], // Use exact case matching
        ['mail_login_email_only', FALSE],    // Allow username fallback
      ]);

    // Mock user storage to return our test user for the email lookup.
    // This simulates finding a user with the exact email address.
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['mail' => $email])
      ->willReturn([$user]);

    // Call lookupAccount with the valid email address.
    $result = $this->authDecorator->lookupAccount($email);

    // Assert that the correct user object is returned.
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

    // The username 'testuser' is not a valid email, so filter_var() will return FALSE.
    // This means the code will skip the email lookup and go to the email_only check.
    // No loadByProperties should be called since it's not a valid email.
    $this->userStorage->expects($this->never())
      ->method('loadByProperties');

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
  public function testCaseInsensitiveConflicts() {
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

    // Looking at the authenticate() method in AuthDecorator, it calls lookupAccount() first.
    // If a user is found and userAuth is NOT UserAuthenticationInterface, it returns the user ID directly.
    // It only calls userAuth.authenticate() if no account is found via lookupAccount().
    $this->userAuth->expects($this->never())
      ->method('authenticate');

    // Call authenticate with valid credentials.
    $result = $this->authDecorator->authenticate($email, $password);

    // Assert correct user ID is returned.
    $this->assertEquals(123, $result);
  }

  /**
   * Test authenticate with invalid credentials using UserAuthenticationInterface.
   */
  public function testAuthenticateWithInvalidCredentials() {
    $email = 'user@example.com';
    $password = 'wrongpassword';
    $user = $this->createMockUser(123, 'testuser', $email, FALSE);

    // Create a UserAuthenticationInterface mock instead of UserAuthInterface.
    $userAuthInterface = $this->createMock(UserAuthenticationInterface::class);
    
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

    // Mock the UserAuthenticationInterface to return FALSE for authentication.
    $userAuthInterface->expects($this->once())
      ->method('authenticateAccount')
      ->with($user, $password)
      ->willReturn(FALSE);

    // Call authenticate with invalid credentials.
    $result = $authDecorator->authenticate($email, $password);

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
   * Test authenticate method with various email formats.
   *
   * @dataProvider emailFormatsProvider
   */
  public function testVariousEmailFormats($email) {
    $password = 'testpassword';
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

    // Call authenticate with various email formats.
    $result = $this->authDecorator->authenticate($email, $password);

    // Assert correct user ID is returned.
    $this->assertEquals(123, $result);
  }

  /**
   * Data provider for various email formats.
   *
   * @return array
   *   Array of email format variations for testing.
   */
  public static function emailFormatsProvider() {
    return [
      'standard_format' => ['user@example.com'],
      'subdomain' => ['user@mail.example.com'],
      'with_dots' => ['first.last@example.com'],
      'with_plus' => ['user+tag@example.com'],
      'with_numbers' => ['user123@example.org'],
      'short_domain' => ['user@ex.co'],
      'long_domain' => ['user@very-long-domain-name.example.com'],
      'international_domain' => ['user@example.co.uk'],
      'hyphenated_local' => ['user-name@example.com'],
      'underscore_local' => ['user_name@example.com'],
    ];
  }

  /**
   * Data provider for edge case identifiers.
   *
   * @return array
   *   Array of edge case identifiers for testing.
   */
  public static function edgeCaseIdentifiersProvider() {
    return [
      'null_value' => [NULL],
      'empty_string' => [''],
      'whitespace_only' => ['   '],
      'very_long_email' => [str_repeat('a', 250) . '@example.com'],
      'unicode_characters' => ['üser@example.com'],
      'special_characters' => ['user!#$%&@example.com'],
    ];
  }

  /**
   * Test authenticate method with edge case scenarios.
   *
   * @dataProvider edgeCaseIdentifiersProvider
   */
  public function testEdgeCaseIdentifiers($identifier) {
    $password = 'testpassword';

    // Configure mail_login_enabled = TRUE.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],
        ['mail_login_case_sensitive', TRUE],
        ['mail_login_email_only', FALSE],
      ]);

    // For edge cases, expect appropriate behavior based on identifier.
    if (empty($identifier)) {
      $this->userStorage->expects($this->never())
        ->method('loadByProperties');
      
      // For empty identifiers, the original userAuth should be called as fallback
      $this->userAuth->expects($this->once())
        ->method('authenticate')
        ->with($identifier, $password)
        ->willReturn(FALSE);
    } else {
      if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        $this->userStorage->expects($this->once())
          ->method('loadByProperties')
          ->with(['mail' => $identifier])
          ->willReturn([]);
      } else {
        $this->userStorage->expects($this->once())
          ->method('loadByProperties')
          ->with(['name' => $identifier])
          ->willReturn([]);
      }
      
      // For non-empty identifiers that don't match users, it falls back to userAuth
      $this->userAuth->expects($this->once())
        ->method('authenticate')
        ->with($identifier, $password)
        ->willReturn(FALSE);
    }

    // Call authenticate with edge case identifier.
    $result = $this->authDecorator->authenticate($identifier, $password);

    // For all edge cases where no user is found, should return FALSE from fallback
    $this->assertFalse($result);
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
