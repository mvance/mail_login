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
   */
  protected function assertLoginSuccess($username) {
    // Check for successful login indicators.
    // After successful login, we should be redirected to the user profile page.
    $this->assertSession()->addressEquals('/user/' . $this->testUser->id());
    
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
