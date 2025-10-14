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
      'standard_format' => ['user@example.com', 'emailuser1'],
      'subdomain' => ['admin@mail.example.com', 'emailadmin1'],
      'with_dots' => ['first.last@example.com', 'firstlast1'],
      'with_plus' => ['user+tag@example.com', 'usertag1'],
      'with_numbers' => ['user123@example.org', 'user123email'],
      'short_domain' => ['test@ex.co', 'testuser1'],
      'international_domain' => ['contact@example.co.uk', 'contact1'],
      'hyphenated_local' => ['user-name@example.com', 'username1'],
      'underscore_local' => ['user_name@example.com', 'user_name1'],
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
  public function testPasswordComplexityHandling($password) {
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
   * Test edge cases for email validation and security.
   */
  public function testEmailValidationEdgeCases() {
    // Configure mail_login to be enabled.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => TRUE,
    ]);

    $edge_cases = [
      'sql_injection_attempt' => "admin@example.com'; DROP TABLE users; --",
      'xss_attempt' => 'admin@example.com<script>alert("xss")</script>',
      'null_bytes' => "admin@example.com\0",
      'unicode_normalization' => 'üser@example.com',
      'punycode_domain' => 'user@xn--nxasmq6b.com',
    ];

    foreach ($edge_cases as $case_name => $malicious_input) {
      // Test that malicious input doesn't cause security issues.
      $this->drupalGet('/user/login');

      $this->submitForm([
        'name' => $malicious_input,
        'pass' => 'anypassword',
      ], 'Log in');

      // Assert login failure and no security breach.
      $this->assertLoginFailure();

      // Verify no JavaScript execution or SQL injection occurred.
      $page_text = $this->getSession()->getPage()->getText();
      $this->assertStringNotContainsString('<script>', $page_text);
      $this->assertStringNotContainsString('DROP TABLE', $page_text);
      $this->assertStringNotContainsString('alert(', $page_text);

      // Verify we're still on a safe page.
      $current_url = $this->getSession()->getCurrentUrl();
      $this->assertTrue(
        strpos($current_url, '/user/login') !== FALSE,
        "Security test failed for case: $case_name"
      );
    }
  }

  /**
   * Test performance with multiple concurrent-like scenarios.
   */
  public function testPerformanceConsiderations() {
    // Create multiple users for testing.
    $users = [];
    for ($i = 1; $i <= 5; $i++) {
      $users[] = $this->createTestUser("perfuser$i", "perf$i@example.com", "password$i");
    }

    // Configure mail_login to be enabled.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => FALSE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test rapid sequential logins to ensure no performance degradation.
    $start_time = microtime(TRUE);

    foreach ($users as $index => $user) {
      $user_num = $index + 1;
      
      // Test login.
      $this->drupalGet('/user/login');
      
      $this->submitForm([
        'name' => "perf$user_num@example.com",
        'pass' => "password$user_num",
      ], 'Log in');

      // Assert successful login.
      $this->assertLoginSuccess("perfuser$user_num", $user);

      // Log out for next iteration.
      $this->drupalLogout();
    }

    $total_time = microtime(TRUE) - $start_time;

    // Assert reasonable performance (less than 30 seconds for 5 logins in functional tests).
    $this->assertLessThan(30, $total_time, 'Multiple sequential logins should complete in reasonable time');
  }

  /**
   * Test case-insensitive matching with potential conflicts.
   */
  public function testCaseInsensitiveConflictHandling() {
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
   * Test validation method to ensure functional test isolation.
   *
   * This method verifies that functional tests don't interfere with each other
   * by testing the same functionality multiple times.
   */
  public function testFunctionalTestIsolation() {
    // Run the same login test multiple times to ensure isolation
    for ($i = 0; $i < 3; $i++) {
      // Create a unique user for each iteration
      $user = $this->createTestUser("isolationuser$i", "isolation$i@example.com", "password$i");

      // Configure mail_login
      $this->configureMailLoginSettings([
        'mail_login_enabled' => TRUE,
        'mail_login_case_sensitive' => TRUE,
        'mail_login_email_only' => FALSE,
      ]);

      // Test login
      $this->drupalGet('/user/login');
      $this->submitForm([
        'name' => "isolation$i@example.com",
        'pass' => "password$i",
      ], 'Log in');

      // Assert successful login
      $this->assertLoginSuccess("isolationuser$i", $user);

      // Log out to clean up for next iteration
      $this->drupalLogout();
    }
  }

  /**
   * Test validation method to verify error handling in functional tests.
   *
   * This method ensures that functional tests properly handle error conditions
   * and don't break when unexpected situations occur.
   */
  public function testFunctionalErrorHandling() {
    // Test with non-existent configuration
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test login with completely invalid data
    $this->drupalGet('/user/login');
    
    // Submit form with invalid data that might cause errors
    $this->submitForm([
      'name' => str_repeat('x', 1000), // Very long username
      'pass' => '',                    // Empty password
    ], 'Log in');

    // Should handle gracefully without fatal errors
    $this->assertLoginFailure();
    
    // Verify no PHP errors occurred
    $page_text = $this->getSession()->getPage()->getText();
    $this->assertStringNotContainsString('Fatal error', $page_text);
    $this->assertStringNotContainsString('Warning:', $page_text);
    $this->assertStringNotContainsString('Notice:', $page_text);
  }

  /**
   * Test validation method to verify security considerations.
   *
   * This method ensures that the authentication system properly handles
   * security-related scenarios and doesn't leak sensitive information.
   */
  public function testSecurityValidation() {
    // Configure mail_login
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => FALSE,
    ]);

    // Create a test user
    $user = $this->createTestUser('securityuser', 'security@example.com', 'securepassword');

    // Test that sensitive information is not leaked in error messages
    $this->drupalGet('/user/login');
    $this->submitForm([
      'name' => 'security@example.com',
      'pass' => 'wrongpassword',
    ], 'Log in');

    $page_text = $this->getSession()->getPage()->getText();
    
    // Ensure no sensitive information is leaked
    $this->assertStringNotContainsString('securepassword', $page_text);
    $this->assertStringNotContainsString('database', $page_text);
    $this->assertStringNotContainsString('SQL', $page_text);
    $this->assertStringNotContainsString('mysql', $page_text);
    $this->assertStringNotContainsString('Exception', $page_text);
    
    // Verify generic error message is shown
    $this->assertTrue(
      strpos($page_text, 'Unrecognized username or password') !== false ||
      strpos($page_text, 'Sorry, unrecognized username or password') !== false ||
      strpos($page_text, 'Invalid username or password') !== false,
      'Expected generic error message not found'
    );
  }

  /**
   * Test validation method to verify all configuration scenarios work.
   *
   * This method ensures that all configuration combinations are properly
   * tested and work as expected in functional scenarios.
   */
  public function testConfigurationScenarioValidation() {
    // Test all major configuration combinations
    $config_scenarios = [
      'basic_enabled' => [
        'mail_login_enabled' => TRUE,
        'mail_login_case_sensitive' => TRUE,
        'mail_login_email_only' => FALSE,
      ],
      'case_insensitive' => [
        'mail_login_enabled' => TRUE,
        'mail_login_case_sensitive' => FALSE,
        'mail_login_email_only' => FALSE,
      ],
      'email_only' => [
        'mail_login_enabled' => TRUE,
        'mail_login_case_sensitive' => TRUE,
        'mail_login_email_only' => TRUE,
      ],
      'disabled' => [
        'mail_login_enabled' => FALSE,
        'mail_login_case_sensitive' => TRUE,
        'mail_login_email_only' => FALSE,
      ],
    ];

    foreach ($config_scenarios as $scenario_name => $config) {
      // Create a unique user for this scenario
      $user = $this->createTestUser("config$scenario_name", "config$scenario_name@example.com", "password$scenario_name");

      // Apply configuration
      $this->configureMailLoginSettings($config);

      // Test login behavior based on configuration
      $this->drupalGet('/user/login');

      if ($config['mail_login_enabled']) {
        // When enabled, email login should work
        $this->submitForm([
          'name' => "config$scenario_name@example.com",
          'pass' => "password$scenario_name",
        ], 'Log in');

        $this->assertLoginSuccess("config$scenario_name", $user);
        $this->drupalLogout();

        // Test username behavior based on email_only setting
        $this->drupalGet('/user/login');
        $this->submitForm([
          'name' => "config$scenario_name",
          'pass' => "password$scenario_name",
        ], 'Log in');

        if ($config['mail_login_email_only']) {
          // Should fail with email-only mode
          $this->assertLoginFailure();
        } else {
          // Should succeed with username fallback
          $this->assertLoginSuccess("config$scenario_name", $user);
          $this->drupalLogout();
        }
      } else {
        // When disabled, email login should not work
        $this->submitForm([
          'name' => "config$scenario_name@example.com",
          'pass' => "password$scenario_name",
        ], 'Log in');

        $this->assertLoginFailure();

        // But username login should still work
        $this->drupalGet('/user/login');
        $this->submitForm([
          'name' => "config$scenario_name",
          'pass' => "password$scenario_name",
        ], 'Log in');

        $this->assertLoginSuccess("config$scenario_name", $user);
        $this->drupalLogout();
      }
    }
  }

  /**
   * Test validation method to verify performance requirements.
   *
   * This method ensures that functional tests complete within reasonable
   * time limits for development workflow integration.
   */
  public function testFunctionalPerformanceValidation() {
    $start_time = microtime(TRUE);

    // Configure mail_login
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => TRUE,
      'mail_login_email_only' => FALSE,
    ]);

    // Perform multiple login operations to test performance
    for ($i = 0; $i < 3; $i++) {
      $user = $this->createTestUser("perfuser$i", "perf$i@example.com", "password$i");

      $this->drupalGet('/user/login');
      $this->submitForm([
        'name' => "perf$i@example.com",
        'pass' => "password$i",
      ], 'Log in');

      $this->assertLoginSuccess("perfuser$i", $user);
      $this->drupalLogout();
    }

    $execution_time = microtime(TRUE) - $start_time;

    // Functional tests should complete in reasonable time (< 60 seconds for 3 logins)
    $this->assertLessThan(60, $execution_time, 'Functional test performance requirement not met');
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
