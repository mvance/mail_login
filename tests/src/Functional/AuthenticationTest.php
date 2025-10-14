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
    $this->assertSession()->addressEquals('/user/' . $this->testUser->id());
    $this->assertSession()->pageTextContains('Member for');
    $this->assertSession()->linkExists('Log out');
  }

  /**
   * Helper method to assert login failure.
   *
   * @param string $error_message
   *   Optional error message to check for.
   */
  protected function assertLoginFailure($error_message = NULL) {
    // Should still be on login page.
    $this->assertSession()->addressEquals('/user/login');
    
    if ($error_message) {
      $this->assertSession()->pageTextContains($error_message);
    }
    
    // Should not see logout link.
    $this->assertSession()->linkNotExists('Log out');
  }

}
