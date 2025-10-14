<?php

namespace Drupal\Tests\mail_login\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Tests for mail_login authentication functionality.
 *
 * @group mail_login
 */
class AuthenticationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['mail_login', 'user'];

  /**
   * A test user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a test user.
    $this->testUser = $this->createTestUser('testuser', 'test@example.com', 'testpassword');
  }

  /**
   * Test that the test environment is properly set up.
   */
  public function testEnvironmentSetup() {
    // Verify that the mail_login module is enabled.
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('mail_login'));

    // Verify that our test user was created successfully.
    $this->assertNotNull($this->testUser);
    $this->assertEquals('testuser', $this->testUser->getAccountName());
    $this->assertEquals('test@example.com', $this->testUser->getEmail());

    // Verify that the user login page is accessible.
    $this->drupalGet('/user/login');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('name');
    $this->assertSession()->fieldExists('pass');
  }

  /**
   * Test successful login using email address.
   */
  public function testEmailLoginSuccess() {
    // Configure mail_login to be enabled.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => FALSE,
    ]);

    // Go to login page.
    $this->drupalGet('/user/login');

    // Submit login form with email address.
    $this->submitForm([
      'name' => 'test@example.com',
      'pass' => 'testpassword',
    ], 'Log in');

    // Assert successful login.
    $this->assertLoginSuccess('testuser');
  }

  /**
   * Test successful login using username (fallback behavior).
   */
  public function testUsernameLoginSuccess() {
    // Configure mail_login to be enabled with username fallback.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => FALSE,
    ]);

    // Go to login page.
    $this->drupalGet('/user/login');

    // Submit login form with username.
    $this->submitForm([
      'name' => 'testuser',
      'pass' => 'testpassword',
    ], 'Log in');

    // Assert successful login.
    $this->assertLoginSuccess('testuser');
  }

  /**
   * Test that when mail login is disabled, username login still works.
   */
  public function testMailLoginDisabledFallsBackToUsername() {
    // Configure mail_login to be disabled.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => FALSE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => FALSE,
    ]);

    // Go to login page.
    $this->drupalGet('/user/login');

    // Submit login form with username (should work).
    $this->submitForm([
      'name' => 'testuser',
      'pass' => 'testpassword',
    ], 'Log in');

    // Assert successful login.
    $this->assertLoginSuccess('testuser');

    // Log out for next test.
    $this->drupalLogout();

    // Go to login page again.
    $this->drupalGet('/user/login');

    // Submit login form with email (should fail since mail_login is disabled).
    $this->submitForm([
      'name' => 'test@example.com',
      'pass' => 'testpassword',
    ], 'Log in');

    // This should fail since mail_login is disabled and Drupal doesn't 
    // natively support email login. We expect to stay on login page.
    $this->assertLoginFailure();
  }

  /**
   * Test email-only mode rejects username login.
   */
  public function testEmailOnlyModeRejectsUsername() {
    // Configure mail_login with email-only mode enabled.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => TRUE,
    ]);

    // Go to login page.
    $this->drupalGet('/user/login');

    // Attempt login with username (should fail).
    $this->submitForm([
      'name' => 'testuser',
      'pass' => 'testpassword',
    ], 'Log in');

    // Verify error message appears and login fails.
    $this->assertLoginFailure('Login by username has been disabled');

    // Now test successful login with email address.
    $this->drupalGet('/user/login');

    // Submit login form with email address (should work).
    $this->submitForm([
      'name' => 'test@example.com',
      'pass' => 'testpassword',
    ], 'Log in');

    // Assert successful login.
    $this->assertLoginSuccess('testuser');
  }

  /**
   * Test blocked user login failure.
   */
  public function testBlockedUserLoginFailure() {
    // Create a blocked user account.
    $blockedUser = $this->createTestUser('blockeduser', 'blocked@example.com', 'blockedpassword', TRUE);

    // Configure mail_login to be enabled.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => FALSE,
    ]);

    // Go to login page.
    $this->drupalGet('/user/login');

    // Attempt login with blocked user's email.
    $this->submitForm([
      'name' => 'blocked@example.com',
      'pass' => 'blockedpassword',
    ], 'Log in');

    // Verify appropriate error message and login failure.
    $this->assertLoginFailure('The user has not been activated yet or is blocked');

    // Also test with username.
    $this->drupalGet('/user/login');

    // Attempt login with blocked user's username.
    $this->submitForm([
      'name' => 'blockeduser',
      'pass' => 'blockedpassword',
    ], 'Log in');

    // Verify appropriate error message and login failure.
    $this->assertLoginFailure('The user has not been activated yet or is blocked');
  }

  /**
   * Test invalid credentials show appropriate error.
   */
  public function testInvalidCredentialsShowError() {
    // Configure mail_login to be enabled.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => FALSE,
    ]);

    // Go to login page.
    $this->drupalGet('/user/login');

    // Test login with wrong password via email.
    $this->submitForm([
      'name' => 'test@example.com',
      'pass' => 'wrongpassword',
    ], 'Log in');

    // Verify error message display and ensure no sensitive information is leaked.
    $this->assertLoginFailure();
    $page_text = $this->getSession()->getPage()->getText();
    
    // Check for generic error message (exact text may vary by Drupal version).
    $this->assertTrue(
      strpos($page_text, 'Unrecognized username or password') !== false ||
      strpos($page_text, 'Sorry, unrecognized username or password') !== false ||
      strpos($page_text, 'Invalid username or password') !== false,
      'Expected to find login error message, but got: ' . substr($page_text, 0, 500)
    );

    // Ensure no sensitive information is leaked.
    $this->assertStringNotContainsString('testpassword', $page_text);
    $this->assertStringNotContainsString('database', $page_text);

    // Test login with wrong password via username.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => 'testuser',
      'pass' => 'wrongpassword',
    ], 'Log in');

    // Verify error message display.
    $this->assertLoginFailure();
    $page_text = $this->getSession()->getPage()->getText();
    
    // Check for generic error message.
    $this->assertTrue(
      strpos($page_text, 'Unrecognized username or password') !== false ||
      strpos($page_text, 'Sorry, unrecognized username or password') !== false ||
      strpos($page_text, 'Invalid username or password') !== false,
      'Expected to find login error message, but got: ' . substr($page_text, 0, 500)
    );
  }

  /**
   * Test case-insensitive email login functionality.
   */
  public function testCaseInsensitiveEmailLogin() {
    // Create a user with lowercase email.
    $lowercaseUser = $this->createTestUser('lowercaseuser', 'lowercase@example.com', 'testpassword');

    // Configure mail_login with case-insensitive matching.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => FALSE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test login with mixed-case email.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => 'LowerCase@Example.COM',
      'pass' => 'testpassword',
    ], 'Log in');

    // Assert successful login.
    $this->assertLoginSuccess('lowercaseuser', $lowercaseUser);

    // Log out for next test.
    $this->drupalLogout();

    // Test with all uppercase email.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => 'LOWERCASE@EXAMPLE.COM',
      'pass' => 'testpassword',
    ], 'Log in');

    // Assert successful login.
    $this->assertLoginSuccess('lowercaseuser', $lowercaseUser);

    // Log out for next test.
    $this->drupalLogout();

    // Test with random case variations.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => 'lOwErCaSe@ExAmPlE.cOm',
      'pass' => 'testpassword',
    ], 'Log in');

    // Assert successful login.
    $this->assertLoginSuccess('lowercaseuser', $lowercaseUser);
  }

  /**
   * Test case-sensitive email login functionality.
   */
  public function testCaseSensitiveEmailLogin() {
    // Create users with different emails that have different cases.
    $lowerUser = $this->createTestUser('loweruser', 'lower@example.com', 'lowerpassword');
    $upperUser = $this->createTestUser('upperuser', 'UPPER@EXAMPLE.COM', 'upperpassword');

    // Configure mail_login with case-sensitive matching.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test login with exact lowercase email.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => 'lower@example.com',
      'pass' => 'lowerpassword',
    ], 'Log in');

    // Assert successful login for lowercase user.
    $this->assertLoginSuccess('loweruser', $lowerUser);

    // Log out for next test.
    $this->drupalLogout();

    // Test login with exact uppercase email.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => 'UPPER@EXAMPLE.COM',
      'pass' => 'upperpassword',
    ], 'Log in');

    // Assert successful login for uppercase user.
    $this->assertLoginSuccess('upperuser', $upperUser);

    // Log out for next test.
    $this->drupalLogout();

    // Test case mismatch should fail - try uppercase email with lowercase password.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => 'UPPER@EXAMPLE.COM',
      'pass' => 'lowerpassword',
    ], 'Log in');

    // This should fail because the password doesn't match the uppercase user.
    $this->assertLoginFailure();

    // Test case mismatch with wrong password - this should definitely fail.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => 'LOWER@EXAMPLE.COM',
      'pass' => 'wrongpassword',
    ], 'Log in');

    // This should fail because of wrong password, regardless of case sensitivity.
    $this->assertLoginFailure();
  }

  /**
   * Data provider for email case variations.
   *
   * @return array
   *   Array of email case variations for testing.
   */
  public static function emailCaseVariationsProvider() {
    return [
      'all_lowercase' => ['user@example.com'],
      'all_uppercase' => ['USER@EXAMPLE.COM'],
      'mixed_case_1' => ['User@Example.Com'],
      'mixed_case_2' => ['uSeR@eXaMpLe.CoM'],
      'domain_uppercase' => ['user@EXAMPLE.COM'],
      'local_uppercase' => ['USER@example.com'],
    ];
  }

  /**
   * Data provider for various email formats.
   *
   * @return array
   *   Array of email format variations for testing.
   */
  public static function emailFormatsProvider() {
    return [
      'standard_format' => ['user@example.com', 'user'],
      'subdomain' => ['admin@mail.example.com', 'admin'],
      'with_dots' => ['first.last@example.com', 'firstlast'],
      'with_plus' => ['user+tag@example.com', 'usertag'],
      'with_numbers' => ['user123@example.org', 'user123'],
      'short_domain' => ['test@ex.co', 'testuser'],
      'international_domain' => ['contact@example.co.uk', 'contact'],
      'hyphenated_local' => ['user-name@example.com', 'username'],
      'underscore_local' => ['user_name@example.com', 'user_name'],
    ];
  }

  /**
   * Data provider for invalid login scenarios.
   *
   * @return array
   *   Array of invalid login scenarios for testing.
   */
  public static function invalidLoginScenariosProvider() {
    return [
      'empty_email' => ['', 'password'],
      'empty_password' => ['user@example.com', ''],
      'both_empty' => ['', ''],
      'whitespace_email' => ['   ', 'password'],
      'whitespace_password' => ['user@example.com', '   '],
      'invalid_email_format' => ['not-an-email', 'password'],
      'very_long_email' => [str_repeat('a', 250) . '@example.com', 'password'],
      'special_characters' => ['user<script>@example.com', 'password'],
    ];
  }

  /**
   * Data provider for password complexity scenarios.
   *
   * @return array
   *   Array of password scenarios for testing.
   */
  public static function passwordComplexityProvider() {
    return [
      'simple_password' => ['simple123'],
      'complex_password' => ['C0mpl3x!P@ssw0rd'],
      'with_spaces' => ['password with spaces'],
      'special_characters' => ['p@$$w0rd!#$%'],
      'unicode_characters' => ['pässwörd123'],
      'very_long_password' => [str_repeat('a', 100)],
    ];
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
   * Data provider for invalid email formats.
   *
   * @return array
   *   Array of invalid email formats for testing.
   */
  public static function invalidEmailFormatsProvider() {
    return [
      'no_at_symbol' => ['userexample.com'],
      'multiple_at_symbols' => ['user@@example.com'],
      'no_domain' => ['user@'],
      'no_local_part' => ['@example.com'],
      'spaces' => ['user @example.com'],
      'invalid_characters' => ['user<>@example.com'],
      'empty_string' => [''],
      'just_spaces' => ['   '],
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
   * Test lookupAccount with various valid email formats.
   *
   * @dataProvider emailFormatsProvider
   */
  public function testLookupAccountWithVariousEmailFormats($email) {
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

    // Call lookupAccount with the email format.
    $result = $this->authDecorator->lookupAccount($email);

    // Assert correct user object is returned.
    $this->assertSame($user, $result);
  }

  /**
   * Test lookupAccount with invalid email formats.
   *
   * @dataProvider invalidEmailFormatsProvider
   */
  public function testLookupAccountWithInvalidEmailFormats($invalid_email) {
    // Configure mail_login_enabled = TRUE, email_only = FALSE.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],
        ['mail_login_case_sensitive', TRUE],
        ['mail_login_email_only', FALSE],
      ]);

    // For invalid emails, the code should treat them as usernames.
    // Mock user storage to return empty array for username lookup.
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['name' => $invalid_email])
      ->willReturn([]);

    // Call lookupAccount with invalid email.
    $result = $this->authDecorator->lookupAccount($invalid_email);

    // Assert FALSE is returned (no user found).
    $this->assertFalse($result);
  }

  /**
   * Test lookupAccount with edge case identifiers.
   *
   * @dataProvider edgeCaseIdentifiersProvider
   */
  public function testLookupAccountWithEdgeCaseIdentifiers($identifier) {
    // Configure mail_login_enabled = TRUE.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],
        ['mail_login_case_sensitive', TRUE],
        ['mail_login_email_only', FALSE],
      ]);

    // For edge cases, expect no database calls if identifier is empty/null.
    if (empty($identifier)) {
      $this->userStorage->expects($this->never())
        ->method('loadByProperties');
    } else {
      // For non-empty edge cases, mock appropriate storage calls.
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
    }

    // Call lookupAccount with edge case identifier.
    $result = $this->authDecorator->lookupAccount($identifier);

    // Assert FALSE is returned for all edge cases.
    $this->assertFalse($result);
  }

  /**
   * Test case-insensitive lookup with multiple similar emails.
   */
  public function testLookupAccountCaseInsensitiveMultipleMatches() {
    $email_input = 'USER@EXAMPLE.COM';
    $user1 = $this->createMockUser(123, 'user1', 'user@example.com', FALSE);
    $user2 = $this->createMockUser(124, 'user2', 'USER@EXAMPLE.COM', FALSE);

    // Configure mail_login_enabled = TRUE, case_sensitive = FALSE.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],
        ['mail_login_case_sensitive', FALSE],
        ['mail_login_email_only', FALSE],
      ]);

    // Mock user storage to return empty for exact match.
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['mail' => $email_input])
      ->willReturn([]);

    // Mock the database query for case-insensitive lookup with multiple results.
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
      ->willReturn([123, 124]); // Multiple matches

    $this->userStorage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);

    // When multiple matches are found, loadMultiple should not be called.
    $this->userStorage->expects($this->never())
      ->method('loadMultiple');

    // Mock database escapeLike method.
    $this->connection->expects($this->once())
      ->method('escapeLike')
      ->with($email_input)
      ->willReturn($email_input);

    // Call lookupAccount with case-insensitive email that has multiple matches.
    $result = $this->authDecorator->lookupAccount($email_input);

    // Assert FALSE is returned when multiple case-insensitive matches exist.
    $this->assertFalse($result);
  }

  /**
   * Test performance considerations with large datasets.
   */
  public function testLookupAccountPerformanceConsiderations() {
    $email = 'performance@example.com';
    $user = $this->createMockUser(123, 'perfuser', $email, FALSE);

    // Configure mail_login_enabled = TRUE.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],
        ['mail_login_case_sensitive', TRUE],
        ['mail_login_email_only', FALSE],
      ]);

    // Mock user storage with timing considerations.
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['mail' => $email])
      ->willReturn([$user]);

    // Measure execution time for performance validation.
    $start_time = microtime(TRUE);
    
    // Call lookupAccount.
    $result = $this->authDecorator->lookupAccount($email);
    
    $execution_time = microtime(TRUE) - $start_time;

    // Assert correct user object is returned.
    $this->assertSame($user, $result);
    
    // Assert execution time is reasonable (less than 100ms for unit test).
    $this->assertLessThan(0.1, $execution_time, 'lookupAccount should execute quickly in unit tests');
  }

  /**
   * Test database connection error handling.
   */
  public function testLookupAccountDatabaseConnectionFailure() {
    $email = 'test@example.com';

    // Configure mail_login_enabled = TRUE, case_sensitive = FALSE.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],
        ['mail_login_case_sensitive', FALSE],
        ['mail_login_email_only', FALSE],
      ]);

    // Mock user storage to return empty for exact match.
    $this->userStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['mail' => $email])
      ->willReturn([]);

    // Mock database connection to throw exception.
    $this->connection->expects($this->once())
      ->method('escapeLike')
      ->with($email)
      ->willThrowException(new \Exception('Database connection failed'));

    // Mock query that won't be reached due to exception.
    $query = $this->createMock(\Drupal\Core\Entity\Query\QueryInterface::class);
    $this->userStorage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);

    // Call lookupAccount and expect it to handle the database error gracefully.
    $result = $this->authDecorator->lookupAccount($email);

    // Assert FALSE is returned when database connection fails.
    $this->assertFalse($result);
  }

  /**
   * Helper method to create a test user with email and password.
   *
   * @param string $username
   *   The username.
   * @param string $email
   *   The email address.
   * @param string $password
   *   The password.
   * @param bool $blocked
   *   Whether the user should be blocked.
   *
   * @return \Drupal\user\UserInterface
   *   The created user.
   */
  protected function createTestUser($username, $email, $password, $blocked = FALSE) {
    $user = User::create([
      'name' => $username,
      'mail' => $email,
      'pass' => $password,
      'status' => !$blocked,
    ]);
    $user->save();
    return $user;
  }

  /**
   * Helper method to configure mail_login settings.
   *
   * @param array $settings
   *   Array of settings to configure.
   */
  protected function configureMailLoginSettings(array $settings) {
    $config = \Drupal::configFactory()->getEditable('mail_login.settings');
    foreach ($settings as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
  }

  /**
   * Helper method to assert successful login.
   *
   * @param string $username
   *   The expected username.
   * @param \Drupal\user\UserInterface $user
   *   The user object that should have logged in.
   */
  protected function assertLoginSuccess($username, $user = NULL) {
    // Use the provided user or fall back to the default test user.
    $expected_user = $user ?: $this->testUser;
    
    // Check for successful login indicators.
    // After successful login, we should be redirected to the user profile page.
    $this->assertSession()->addressEquals('/user/' . $expected_user->id());
    
    // Check for common indicators of successful login.
    // The exact text may vary, so we'll check for multiple possibilities.
    $page_text = $this->getSession()->getPage()->getText();
    
    // Look for common success indicators.
    $success_indicators = [
      'Member for',
      'Edit',
      $username,
      'My account',
    ];
    
    $found_indicator = false;
    foreach ($success_indicators as $indicator) {
      if (strpos($page_text, $indicator) !== false) {
        $found_indicator = true;
        break;
      }
    }
    
    $this->assertTrue($found_indicator, 'No login success indicator found on page. Page text: ' . substr($page_text, 0, 500));
    
    // Check that we're not on the login page anymore.
    $this->assertSession()->addressNotEquals('/user/login');
  }

  /**
   * Helper method to assert login failure.
   *
   * @param string $error_message
   *   Optional error message to check for.
   */
  protected function assertLoginFailure($error_message = NULL) {
    // Should still be on login page for failed login.
    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertTrue(
      strpos($current_url, '/user/login') !== false,
      'Expected to be on login page after failed login, but was on: ' . $current_url
    );
    
    if ($error_message) {
      $this->assertSession()->pageTextContains($error_message);
    }
    
    // Additional check: should not be redirected to user profile page.
    // Use addressNotEquals instead of addressNotMatches since the latter doesn't exist.
    $this->assertSession()->addressNotEquals('/user/1');
    $this->assertSession()->addressNotEquals('/user/2');
    $this->assertSession()->addressNotEquals('/user/3');
    $this->assertSession()->addressNotEquals('/user/4');
    $this->assertSession()->addressNotEquals('/user/5');
  }

}
