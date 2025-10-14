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

    // Test another case mismatch - try mixed case email that doesn't exactly match either.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => 'Lower@Example.Com',
      'pass' => 'lowerpassword',
    ], 'Log in');

    // This should fail because case-sensitive mode requires exact email match.
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
   * Test multiple email case variations with case-insensitive mode.
   *
   * @dataProvider emailCaseVariationsProvider
   */
  public function testEmailCaseVariationsInsensitive($email_variant) {
    // Create a user with standard lowercase email.
    $standardUser = $this->createTestUser('standarduser', 'user@example.com', 'standardpassword');

    // Configure mail_login with case-insensitive matching.
    $this->configureMailLoginSettings([
      'mail_login_enabled' => TRUE,
      'mail_login_case_sensitive' => FALSE,
      'mail_login_email_only' => FALSE,
    ]);

    // Test login with the email variant.
    $this->drupalGet('/user/login');

    $this->submitForm([
      'name' => $email_variant,
      'pass' => 'standardpassword',
    ], 'Log in');

    // Assert successful login regardless of case.
    $this->assertLoginSuccess('standarduser', $standardUser);

    // Clean up by logging out.
    $this->drupalLogout();
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
    // Should still be on login page or show error.
    $current_url = $this->getSession()->getCurrentUrl();
    $this->assertTrue(
      strpos($current_url, '/user/login') !== false || strpos($current_url, '/user') !== false,
      'Expected to be on login page or user page, but was on: ' . $current_url
    );
    
    if ($error_message) {
      $this->assertSession()->pageTextContains($error_message);
    }
    
    // Check that we don't have success indicators.
    $page_text = $this->getSession()->getPage()->getText();
    $this->assertStringNotContainsString('Member for', $page_text);
  }

}
