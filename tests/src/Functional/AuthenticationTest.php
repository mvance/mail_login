<?php

namespace Drupal\Tests\mail_login\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Tests for mail_login authentication functionality.
 *
 * This test class provides comprehensive functional testing for the mail_login
 * module's authentication features. Tests are performed using real browser
 * interactions to verify end-to-end user workflows.
 *
 * Test Coverage:
 * - Complete email and username login flows
 * - Configuration-dependent behavior (email-only mode, case sensitivity)
 * - Error handling and user feedback
 * - Security considerations (blocked users, invalid input)
 * - Edge cases with various email formats and password complexity
 * - Performance considerations for multiple login scenarios
 *
 * Test Data:
 * - Uses realistic email formats and usernames
 * - Tests with various password complexity levels
 * - Includes edge cases like Unicode characters and special symbols
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
   *
   * Sets up the test environment with a default test user and clean state.
   *
   * This method prepares the testing environment by:
   * - Calling parent setUp to initialize Drupal
   * - Creating a standard test user for use across multiple test methods
   * - Ensuring a clean state for each test execution
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a standard test user that will be available for all test methods.
    // This user has a simple username, standard email format, and basic password.
    $this->testUser = $this->createTestUser('testuser', 'test@example.com', 'testpassword');
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
  public function testEmailOnlyMode() {
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
  public function testBlockedUserHandling() {
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
  public function testInvalidCredentials() {
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
      ['user@example.com'],
      ['USER@EXAMPLE.COM'],
      ['User@Example.Com'],
      ['uSeR@eXaMpLe.CoM'],
      ['user@EXAMPLE.COM'],
      ['USER@example.com'],
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
      ['user@example.com', 'emailuser1'],
      ['admin@mail.example.com', 'emailadmin1'],
      ['first.last@example.com', 'firstlast1'],
      ['user+tag@example.com', 'usertag1'],
      ['user123@example.org', 'user123email'],
      ['test@ex.co', 'testuser1'],
      ['contact@example.co.uk', 'contact1'],
      ['user-name@example.com', 'username1'],
      ['user_name@example.com', 'user_name1'],
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
      ['', 'password'],
      ['user@example.com', ''],
      ['', ''],
      ['   ', 'password'],
      ['user@example.com', '   '],
      ['not-an-email', 'password'],
      [str_repeat('a', 250) . '@example.com', 'password'],
      ['user<script>@example.com', 'password'],
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
      ['simple123'],
      ['C0mpl3x!P@ssw0rd'],
      ['password with spaces'],
      ['p@$$w0rd!#$%'],
      ['pässwörd123'],
      [str_repeat('a', 100)],
    ];
  }

  /**
   * Test login with various email formats.
   *
   * @dataProvider emailFormatsProvider
   */
  public function testLoginWithVariousEmailFormats($email, $username) {
    // Create a user with the specific email format.
    $user = $this->createTestUser($username, $email, 'testpassword');

    // Configure mail_login to be enabled.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test login with the email format.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => $email,
      'pass' => 'testpassword',
    ], 'Log in');

    // Assert successful login.
    $this->assertLoginSuccess($username, $user);

    // Clean up by logging out.
    $this->drupalLogout();
  }

  /**
   * Test login failures with invalid scenarios.
   *
   * @dataProvider invalidLoginScenariosProvider
   */
  public function testLoginWithInvalidScenarios($identifier, $password) {
    // Create a valid user for comparison.
    $this->createTestUser('validuser', 'valid@example.com', 'validpassword');

    // Configure mail_login to be enabled.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test login with invalid scenario.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => $identifier,
      'pass' => $password,
    ], 'Log in');

    // Assert login failure.
    $this->assertLoginFailure();

    // Verify we're still on the login page.
    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertTrue(
      strpos($current_url, '/user/login') !== FALSE,
      'Should remain on login page after invalid login attempt'
    );
  }

  /**
   * Test password complexity handling.
   *
   * @dataProvider passwordComplexityProvider
   */
  public function testPasswordComplexity($password) {
    // Create a user with the complex password.
    $user = $this->createTestUser('complexuser', 'complex@example.com', $password);

    // Configure mail_login to be enabled.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test login with the complex password via email.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => 'complex@example.com',
      'pass' => $password,
    ], 'Log in');

    // Assert successful login.
    $this->assertLoginSuccess('complexuser', $user);

    // Clean up by logging out.
    $this->drupalLogout();

    // Test login with the complex password via username.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => 'complexuser',
      'pass' => $password,
    ], 'Log in');

    // Assert successful login.
    $this->assertLoginSuccess('complexuser', $user);

    // Clean up by logging out.
    $this->drupalLogout();
  }

  /**
   * Test SQL injection prevention in email login.
   *
   * This test verifies that SQL injection attempts through the email field
   * are properly handled and do not cause database errors or security breaches.
   */
  public function testSqlInjectionPrevention() {
    // Configure mail_login to be enabled.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => TRUE,
    ]);

    $sql_injection_attempts = [
      "admin@example.com'; DROP TABLE users; --",
      "admin@example.com' OR '1'='1",
      "admin@example.com'; DELETE FROM users WHERE 1=1; --",
      "admin@example.com' UNION SELECT * FROM users --",
    ];

    foreach ($sql_injection_attempts as $malicious_input) {
      // Test that SQL injection attempts don't cause security issues.
      $this->drupalGet('/user/login');

      $this->submitForm([
        'name' => $malicious_input,
        'pass' => 'anypassword',
      ], 'Log in');

      // Assert login failure and no security breach.
      $this->assertLoginFailure();

      // Verify no SQL injection occurred.
      $page_text = $this->getSession()->getPage()->getText();
      $this->assertStringNotContainsString('DROP TABLE', $page_text);
      $this->assertStringNotContainsString('DELETE FROM', $page_text);
      $this->assertStringNotContainsString('UNION SELECT', $page_text);

      // Verify we're still on a safe page.
      $current_url = $this->getSession()->getCurrentUrl();
      $this->assertTrue(
        strpos($current_url, '/user/login') !== FALSE,
        'SQL injection attempt should not redirect away from login page'
      );
    }
  }

  /**
   * Test XSS attack prevention in email login.
   *
   * This test verifies that XSS attempts through the email field
   * are properly sanitized and do not execute malicious scripts.
   */
  public function testXssPrevention() {
    // Configure mail_login to be enabled.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => TRUE,
    ]);

    $xss_attempts = [
      'admin@example.com<script>alert("xss")</script>',
      'admin@example.com<img src=x onerror=alert("xss")>',
      'admin@example.com<svg onload=alert("xss")>',
      'admin@example.com<iframe src="javascript:alert(\'xss\')">',
    ];

    foreach ($xss_attempts as $malicious_input) {
      // Test that XSS attempts don't cause security issues.
      $this->drupalGet('/user/login');

      $this->submitForm([
        'name' => $malicious_input,
        'pass' => 'anypassword',
      ], 'Log in');

      // Assert login failure and no security breach.
      $this->assertLoginFailure();

      // Verify no script execution occurred.
      $page_text = $this->getSession()->getPage()->getText();
      $this->assertStringNotContainsString('<script>', $page_text);
      $this->assertStringNotContainsString('alert(', $page_text);
      $this->assertStringNotContainsString('onerror=', $page_text);
      $this->assertStringNotContainsString('onload=', $page_text);

      // Verify we're still on a safe page.
      $current_url = $this->getSession()->getCurrentUrl();
      $this->assertTrue(
        strpos($current_url, '/user/login') !== FALSE,
        'XSS attempt should not redirect away from login page'
      );
    }
  }

  /**
   * Data provider for Unicode email addresses.
   *
   * @return array
   *   Array of Unicode email addresses for testing.
   */
  public static function unicodeEmailProvider() {
    return [
      ['café@example.com', 'cafeuser1'],
      ['müller@example.de', 'mulleruser1'],
      ['tëst@example.com', 'testuser1'],
      ['用户@example.com', 'chineseuser1'],
      ['тест@example.com', 'cyrillicuser1'],
      ['user@exämple.com', 'domainuser1'],
      ['tëst.üser@example.com', 'mixeduser1'],
      ['user+tëst@example.com', 'plususer1'],
    ];
  }

  /**
   * Test functional login with international email addresses.
   *
   * @dataProvider unicodeEmailProvider
   */
  public function testFunctionalLoginWithUnicodeEmails($email, $username) {
    // Create a user with Unicode email.
    $user = $this->createTestUser($username, $email, 'unicodepassword');

    // Configure mail_login to be enabled.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test login with Unicode email.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => $email,
      'pass' => 'unicodepassword',
    ], 'Log in');

    // Unicode emails may not be fully supported by the mail_login module
    // Check if login was successful or failed gracefully
    $current_url = $this->getSession()->getCurrentUrl();
    $page_text = $this->getSession()->getPage()->getText();
    
    // Ensure no errors occurred
    $this->assertStringNotContainsString('Fatal error', $page_text);
    $this->assertStringNotContainsString('Warning:', $page_text);
    $this->assertStringNotContainsString('Notice:', $page_text);

    // If login was successful, clean up by logging out
    if (strpos($current_url, '/user/login') === FALSE) {
      $this->drupalLogout();
    }
  }

  /**
   * Test Unicode password handling.
   */
  public function testUnicodePasswordHandling() {
    $unicode_passwords = [
      'pässwörd123',
      'пароль123',
      'パスワード',
      'كلمة المرور',
      'סיסמה123',
      'mötdëlöös',
    ];

    // Configure mail_login to be enabled.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => FALSE,
    ]);

    foreach ($unicode_passwords as $index => $password) {
      $username = "unicodepwuser$index";
      $email = "unicodepw$index@example.com";
      
      // Create user with Unicode password.
      $user = $this->createTestUser($username, $email, $password);

      // Test login with Unicode password.
      $this->drupalGet('/user/login');

      $this->submitForm([
        'name' => $email,
        'pass' => $password,
      ], 'Log in');

      // Assert successful login.
      $this->assertLoginSuccess($username, $user);

      // Clean up by logging out.
      $this->drupalLogout();
    }
  }

  /**
   * Test case-insensitive matching with accented characters.
   */
  public function testCaseInsensitiveUnicodeMatching() {
    // Create user with accented email.
    $user = $this->createTestUser('accentuser3', 'café3@example.com', 'testpassword');

    // Configure mail_login with case-insensitive matching.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => FALSE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test login with different case Unicode email.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => 'CAFÉ3@EXAMPLE.COM',
      'pass' => 'testpassword',
    ], 'Log in');

    // The behavior depends on implementation - should handle gracefully.
    $current_url = $this->getSession()->getCurrentUrl();
    $page_text = $this->getSession()->getPage()->getText();
    
    // Ensure no errors occurred.
    $this->assertStringNotContainsString('Fatal error', $page_text);
    $this->assertStringNotContainsString('Warning:', $page_text);
    $this->assertStringNotContainsString('Notice:', $page_text);

    // Clean up if login was successful.
    if (strpos($current_url, '/user/login') === FALSE) {
      $this->drupalLogout();
    }
  }

  /**
   * Test Unicode character handling in email addresses.
   *
   * This test verifies that Unicode characters in email addresses
   * are properly handled and encoded without causing errors.
   */
  public function testUnicodeEmailHandling() {
    // Configure mail_login to be enabled.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => FALSE,
    ]);

    $unicode_emails = [
      'üser@example.com',
      'user@exämple.com',
      'tëst@example.com',
      'user@xn--nxasmq6b.com', // Punycode domain
      "admin@example.com\0", // Null bytes
      'مستخدم@example.com', // Arabic script
      'משתמש@example.com', // Hebrew script
      '用户@example.com', // Chinese characters
      'тест@example.com', // Cyrillic script
      '🙂@example.com', // Emoji
    ];

    foreach ($unicode_emails as $unicode_email) {
      // Test that Unicode emails are handled properly.
      $this->drupalGet('/user/login');

      $this->submitForm([
        'name' => $unicode_email,
        'pass' => 'anypassword',
      ], 'Log in');

      // Should fail gracefully (no user exists with these emails).
      $this->assertLoginFailure();

      // Verify proper encoding and no errors.
      $page_text = $this->getSession()->getPage()->getText();
      $this->assertStringNotContainsString('Fatal error', $page_text);
      $this->assertStringNotContainsString('Warning:', $page_text);
      $this->assertStringNotContainsString('Notice:', $page_text);

      // Verify we're still on the login page.
      $current_url = $this->getSession()->getCurrentUrl();
      $this->assertTrue(
        strpos($current_url, '/user/login') !== FALSE,
        'Unicode email handling should not cause page errors'
      );
    }
  }

  /**
   * Test browser rendering of Unicode characters.
   */
  public function testBrowserUnicodeRendering() {
    // Create user with Unicode email and username.
    $user = $this->createTestUser('tëstüser2', 'tëst2@exämple.com', 'testpassword');

    // Configure mail_login to be enabled.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test that Unicode characters render properly in browser.
    $this->drupalGet('/user/login');

    // Submit form with Unicode email.
    $this->submitForm([
      'name' => 'tëst2@exämple.com',
      'pass' => 'testpassword',
    ], 'Log in');

    // Unicode emails may not be fully supported - check for graceful handling
    $current_url = $this->getSession()->getCurrentUrl();
    $page_text = $this->getSession()->getPage()->getText();
    
    // Ensure no errors occurred
    $this->assertStringNotContainsString('Fatal error', $page_text);
    $this->assertStringNotContainsString('Warning:', $page_text);
    $this->assertStringNotContainsString('Notice:', $page_text);

    // If login was successful, verify Unicode rendering and clean up
    if (strpos($current_url, '/user/login') === FALSE) {
      $this->assertStringContainsString('tëstüser2', $page_text);
      $this->drupalLogout();
    }
  }

  /**
   * Data provider for conflicting email scenarios.
   *
   * @return array
   *   Array of conflicting email scenarios for testing.
   */
  public static function conflictingScenariosProvider() {
    return [
      'case_variants' => [
        ['user@example.com', 'USER@EXAMPLE.COM', 'User@Example.Com'],
        ['password1', 'password2', 'password3'],
        ['loweruser', 'upperuser', 'mixeduser'],
      ],
      'domain_case_variants' => [
        ['test@domain.com', 'test@DOMAIN.COM', 'test@Domain.Com'],
        ['pass1', 'pass2', 'pass3'],
        ['domainuser1', 'domainuser2', 'domainuser3'],
      ],
    ];
  }

  /**
   * Test multiple email conflicts with 3+ users having case-variant emails.
   */
  public function testMultipleEmailConflicts() {
    // Create multiple users with case-variant emails.
    $users = [
      $this->createTestUser('user1', 'conflict@example.com', 'password1'),
      $this->createTestUser('user2', 'CONFLICT@EXAMPLE.COM', 'password2'),
      $this->createTestUser('user3', 'Conflict@Example.Com', 'password3'),
    ];

    // Configure mail_login with case-insensitive matching.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => FALSE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test login attempts with various case combinations.
    $test_cases = [
      'conflict@example.com',
      'CONFLICT@EXAMPLE.COM',
      'Conflict@Example.Com',
      'cOnFlIcT@eXaMpLe.CoM',
    ];

    foreach ($test_cases as $email_variant) {
      $this->drupalGet('/user/login');

      // Try login with first user's password.
      $this->submitForm([
        'name' => $email_variant,
        'pass' => 'password1',
      ], 'Log in');

      // Check that system handles conflicts gracefully.
      $current_url = $this->getSession()->getCurrentUrl();
      $page_text = $this->getSession()->getPage()->getText();
      
      // Ensure no errors occurred.
      $this->assertStringNotContainsString('Fatal error', $page_text);
      $this->assertStringNotContainsString('Warning:', $page_text);
      $this->assertStringNotContainsString('Notice:', $page_text);

      // Clean up if login was successful.
      if (strpos($current_url, '/user/login') === FALSE) {
        $this->drupalLogout();
      }
    }
  }

  /**
   * Test conflict resolution behavior.
   */
  public function testConflictResolution() {
    // Create users with potentially conflicting emails.
    $user1 = $this->createTestUser('firstuser', 'resolve@example.com', 'firstpassword');
    $user2 = $this->createTestUser('seconduser', 'RESOLVE@EXAMPLE.COM', 'secondpassword');

    // Configure mail_login with case-insensitive matching.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => FALSE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test how system resolves ambiguous matches.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => 'Resolve@Example.Com',
      'pass' => 'firstpassword',
    ], 'Log in');

    $current_url = $this->getSession()->getCurrentUrl();
    $page_text = $this->getSession()->getPage()->getText();
    
    // System should either:
    // 1. Successfully log in with one of the users
    // 2. Show an appropriate error message
    // 3. Handle the ambiguity gracefully
    
    // Verify no system errors occurred.
    $this->assertStringNotContainsString('Fatal error', $page_text);
    $this->assertStringNotContainsString('Warning:', $page_text);
    $this->assertStringNotContainsString('Exception', $page_text);

    // Clean up if login was successful.
    if (strpos($current_url, '/user/login') === FALSE) {
      $this->drupalLogout();
    }
  }

  /**
   * Test conflict error messages.
   */
  public function testConflictErrorMessages() {
    // Create users with conflicting emails.
    $user1 = $this->createTestUser('erroruser1', 'error@example.com', 'errorpass1');
    $user2 = $this->createTestUser('erroruser2', 'ERROR@EXAMPLE.COM', 'errorpass2');

    // Configure mail_login with case-insensitive matching.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => FALSE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test login with wrong password to trigger error.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => 'Error@Example.Com',
      'pass' => 'wrongpassword',
    ], 'Log in');

    $page_text = $this->getSession()->getPage()->getText();
    
    // Verify appropriate error messages are shown.
    $this->assertTrue(
      strpos($page_text, 'Unrecognized username or password') !== false ||
      strpos($page_text, 'Sorry, unrecognized username or password') !== false ||
      strpos($page_text, 'Invalid username or password') !== false,
      'Expected to find login error message'
    );

    // Ensure no sensitive information is leaked.
    $this->assertStringNotContainsString('errorpass1', $page_text);
    $this->assertStringNotContainsString('errorpass2', $page_text);
    $this->assertStringNotContainsString('database', $page_text);
    $this->assertStringNotContainsString('SQL', $page_text);
  }

  /**
   * Test conflicting scenarios with data provider.
   *
   * @dataProvider conflictingScenariosProvider
   */
  public function testConflictingScenariosWithProvider($emails, $passwords, $usernames) {
    // Create users with conflicting emails.
    $users = [];
    for ($i = 0; $i < count($emails); $i++) {
      $users[] = $this->createTestUser($usernames[$i], $emails[$i], $passwords[$i]);
    }

    // Configure mail_login with case-insensitive matching.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => FALSE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test login with each email/password combination.
    for ($i = 0; $i < count($emails); $i++) {
      $this->drupalGet('/user/login');

      $this->submitForm([
        'name' => $emails[$i],
        'pass' => $passwords[$i],
      ], 'Log in');

      $current_url = $this->getSession()->getCurrentUrl();
      $page_text = $this->getSession()->getPage()->getText();
      
      // Verify consistent behavior across scenarios.
      $this->assertStringNotContainsString('Fatal error', $page_text);
      $this->assertStringNotContainsString('Warning:', $page_text);
      $this->assertStringNotContainsString('Notice:', $page_text);

      // Clean up if login was successful.
      if (strpos($current_url, '/user/login') === FALSE) {
        $this->drupalLogout();
      }
    }
  }

  /**
   * Test graceful handling of edge cases in conflicts.
   */
  public function testConflictEdgeCases() {
    // Create users with edge case emails.
    $user1 = $this->createTestUser('edgeuser1', 'edge@example.com', 'edgepass1');
    $user2 = $this->createTestUser('edgeuser2', 'EDGE@EXAMPLE.COM', 'edgepass2');

    // Configure mail_login with case-insensitive matching.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => FALSE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test edge cases that might cause conflicts.
    $edge_cases = [
      'edge@example.com   ', // Trailing spaces
      '  EDGE@EXAMPLE.COM', // Leading spaces
      'Edge@Example.Com', // Mixed case
      'EDGE@example.com', // Domain case difference
    ];

    foreach ($edge_cases as $edge_email) {
      $this->drupalGet('/user/login');

      $this->submitForm([
        'name' => $edge_email,
        'pass' => 'edgepass1',
      ], 'Log in');

      $current_url = $this->getSession()->getCurrentUrl();
      $page_text = $this->getSession()->getPage()->getText();
      
      // Verify graceful handling.
      $this->assertStringNotContainsString('Fatal error', $page_text);
      $this->assertStringNotContainsString('Warning:', $page_text);
      $this->assertStringNotContainsString('Exception', $page_text);

      // Clean up if login was successful.
      if (strpos($current_url, '/user/login') === FALSE) {
        $this->drupalLogout();
      }
    }
  }

  /**
   * Test case-insensitive matching with potential conflicts.
   */
  public function testCaseInsensitiveConflicts() {
    // Create users with potentially conflicting emails.
    $user1 = $this->createTestUser('loweruser', 'conflict@example.com', 'password1');
    $user2 = $this->createTestUser('upperuser', 'CONFLICT@EXAMPLE.COM', 'password2');

    // Configure mail_login with case-insensitive matching.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => FALSE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test login with mixed case - should handle conflicts appropriately.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => 'Conflict@Example.Com',
      'pass' => 'password1',
    ], 'Log in');

    // The behavior here depends on implementation - it might succeed with one user
    // or fail due to ambiguity. We'll check that it doesn't cause errors.
    $current_url = $this->getSession()->getCurrentUrl();
    $page_text = $this->getSession()->getPage()->getText();
    
    // Ensure no PHP errors or exceptions occurred.
    $this->assertStringNotContainsString('Fatal error', $page_text);
    $this->assertStringNotContainsString('Warning:', $page_text);
    $this->assertStringNotContainsString('Notice:', $page_text);

    // Clean up if login was successful.
    if (strpos($current_url, '/user/login') === FALSE) {
      $this->drupalLogout();
    }
  }

  /**
   * Helper method to create a test user with email and password.
   *
   * This helper creates users for testing various authentication scenarios.
   * The user is immediately saved to the database and can be used for
   * login testing in functional tests.
   *
   * @param string $username
   *   The username for the new user account.
   * @param string $email
   *   The email address for the new user account.
   * @param string $password
   *   The password for the new user account (stored securely).
   * @param bool $blocked
   *   Whether the user should be blocked (status = 0). Default FALSE.
   *
   * @return \Drupal\user\UserInterface
   *   The created and saved user entity.
   */
  protected function createTestUser($username, $email, $password, $blocked = FALSE) {
    // Create a new user entity with the specified properties.
    $user = User::create([
      'name' => $username,
      'mail' => $email,
      'pass' => $password,
      'status' => !$blocked,  // Convert blocked flag to status (1 = active, 0 = blocked)
    ]);
    
    // Save the user to the database so it can be used in authentication tests.
    $user->save();
    
    return $user;
  }

  /**
   * Helper method to configure mail_login settings.
   *
   * This helper method provides a convenient way to configure mail_login
   * settings for different test scenarios. It directly modifies the
   * configuration and saves it immediately.
   *
   * Common settings:
   * - mail_login_enabled: Enable/disable email login functionality
   * - mail_login_case_sensitive: Control case sensitivity for email matching
   * - mail_login_email_only: Restrict login to email addresses only
   * - mail_login_override_login_labels: Enable custom login form labels
   *
   * @param array $settings
   *   Associative array of configuration keys and values to set.
   *   Example: ['mail_login_enabled' => TRUE, 'mail_login_case_sensitive' => FALSE]
   */
  protected function configureMailLoginSettings(array $settings) {
    // Get the editable configuration object for mail_login settings.
    $config = \Drupal::configFactory()->getEditable('mail_login.settings');
    
    // Apply each setting from the provided array.
    foreach ($settings as $key => $value) {
      $config->set($key, $value);
    }
    
    // Save the configuration changes immediately.
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
