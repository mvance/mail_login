<?php

namespace Drupal\Tests\mail_login\Unit\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mail_login\Form\MailLoginAdminSettingsForm;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for the MailLoginAdminSettingsForm class.
 *
 * @group mail_login
 */
class MailLoginAdminSettingsFormTest extends UnitTestCase {

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
   * The MailLoginAdminSettingsForm instance under test.
   *
   * @var \Drupal\mail_login\Form\MailLoginAdminSettingsForm
   */
  protected $form;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create mocks for dependencies.
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->config = $this->createMock(Config::class);

    // Configure config factory to return our config mock.
    $this->configFactory->expects($this->any())
      ->method('getEditable')
      ->with('mail_login.settings')
      ->willReturn($this->config);

    // Create the form instance with mocked dependencies.
    $this->form = new MailLoginAdminSettingsForm($this->configFactory);

    // Mock the string translation service.
    $string_translation = $this->getStringTranslationStub();
    $this->form->setStringTranslation($string_translation);
  }

  /**
   * Test that getFormId returns the correct form ID.
   */
  public function testGetFormId() {
    $form_id = $this->form->getFormId();
    $this->assertEquals('mail_login_form_admin_settings', $form_id);
  }

  /**
   * Test that getEditableConfigNames returns the correct config names.
   */
  public function testGetEditableConfigNames() {
    // Use reflection to access the protected method
    $reflection = new \ReflectionClass($this->form);
    $method = $reflection->getMethod('getEditableConfigNames');
    $method->setAccessible(TRUE);
    
    $config_names = $method->invoke($this->form);
    $expected = ['mail_login.settings'];
    $this->assertEquals($expected, $config_names);
  }

  /**
   * Test buildForm creates the expected form structure.
   */
  public function testBuildForm() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    // Configure config mock to return default values.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],
        ['mail_login_case_sensitive', TRUE],
        ['mail_login_email_only', FALSE],
        ['mail_login_override_login_labels', TRUE],
        ['mail_login_username_title', 'Log in by username/email address'],
        ['mail_login_username_description', 'You can use your username or email address to login.'],
        ['mail_login_email_only_title', 'Login by email address'],
        ['mail_login_email_only_description', 'You can use your email address only to login.'],
        ['mail_login_password_only_description', 'Enter the password that accompanies your email address.'],
        ['mail_login_password_reset_username_title', 'Username or email address'],
        ['mail_login_password_reset_username_description', 'Password reset instructions will be sent to your registered email address.'],
        ['mail_login_password_reset_email_only_title', 'Email address'],
        ['mail_login_password_reset_email_only_description', 'Password reset instructions will be sent to your registered email address.'],
      ]);

    $result = $this->form->buildForm($form, $form_state);

    // Test that the form has the expected structure.
    $this->assertArrayHasKey('general', $result);
    $this->assertEquals('fieldset', $result['general']['#type']);
    $this->assertEquals('General Configurations', $result['general']['#title']);

    // Test main configuration fields.
    $this->assertArrayHasKey('mail_login_enabled', $result['general']);
    $this->assertEquals('checkbox', $result['general']['mail_login_enabled']['#type']);
    $this->assertTrue($result['general']['mail_login_enabled']['#default_value']);

    $this->assertArrayHasKey('mail_login_case_sensitive', $result['general']);
    $this->assertEquals('checkbox', $result['general']['mail_login_case_sensitive']['#type']);
    $this->assertTrue($result['general']['mail_login_case_sensitive']['#default_value']);

    $this->assertArrayHasKey('mail_login_email_only', $result['general']);
    $this->assertEquals('checkbox', $result['general']['mail_login_email_only']['#type']);
    $this->assertFalse($result['general']['mail_login_email_only']['#default_value']);

    $this->assertArrayHasKey('mail_login_override_login_labels', $result['general']);
    $this->assertEquals('checkbox', $result['general']['mail_login_override_login_labels']['#type']);
    $this->assertTrue($result['general']['mail_login_override_login_labels']['#default_value']);

    // Test text field configurations.
    $this->assertArrayHasKey('mail_login_username_title', $result['general']);
    $this->assertEquals('textfield', $result['general']['mail_login_username_title']['#type']);
    $this->assertEquals('Log in by username/email address', $result['general']['mail_login_username_title']['#default_value']);

    $this->assertArrayHasKey('mail_login_username_description', $result['general']);
    $this->assertEquals('textfield', $result['general']['mail_login_username_description']['#type']);
    $this->assertEquals('You can use your username or email address to login.', $result['general']['mail_login_username_description']['#default_value']);

    // Test that form states are configured.
    $this->assertArrayHasKey('#states', $result['general']['mail_login_email_only']);
    $this->assertArrayHasKey('#states', $result['general']['mail_login_override_login_labels']);
  }

  /**
   * Test buildForm with different configuration values.
   */
  public function testBuildFormWithDifferentConfigValues() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    // Configure config mock to return different values.
    $this->config->expects($this->any())
      ->method('get')
      ->willReturnMap([
        ['mail_login_enabled', FALSE],
        ['mail_login_case_sensitive', FALSE],
        ['mail_login_email_only', TRUE],
        ['mail_login_override_login_labels', FALSE],
        ['mail_login_username_title', 'Custom Username Title'],
        ['mail_login_username_description', 'Custom Username Description'],
        ['mail_login_email_only_title', 'Custom Email Title'],
        ['mail_login_email_only_description', 'Custom Email Description'],
        ['mail_login_password_only_description', 'Custom Password Description'],
        ['mail_login_password_reset_username_title', 'Custom Reset Username Title'],
        ['mail_login_password_reset_username_description', 'Custom Reset Username Description'],
        ['mail_login_password_reset_email_only_title', 'Custom Reset Email Title'],
        ['mail_login_password_reset_email_only_description', 'Custom Reset Email Description'],
      ]);

    $result = $this->form->buildForm($form, $form_state);

    // Test that the form reflects the different configuration values.
    $this->assertFalse($result['general']['mail_login_enabled']['#default_value']);
    $this->assertFalse($result['general']['mail_login_case_sensitive']['#default_value']);
    $this->assertTrue($result['general']['mail_login_email_only']['#default_value']);
    $this->assertFalse($result['general']['mail_login_override_login_labels']['#default_value']);

    $this->assertEquals('Custom Username Title', $result['general']['mail_login_username_title']['#default_value']);
    $this->assertEquals('Custom Username Description', $result['general']['mail_login_username_description']['#default_value']);
    $this->assertEquals('Custom Email Title', $result['general']['mail_login_email_only_title']['#default_value']);
    $this->assertEquals('Custom Email Description', $result['general']['mail_login_email_only_description']['#default_value']);
  }

  /**
   * Test submitForm saves configuration correctly.
   */
  public function testSubmitForm() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    // Configure form state to return test values.
    $form_state->expects($this->any())
      ->method('getValue')
      ->willReturnMap([
        ['mail_login_enabled', TRUE],
        ['mail_login_case_sensitive', FALSE],
        ['mail_login_email_only', TRUE],
        ['mail_login_override_login_labels', TRUE],
        ['mail_login_username_title', 'Test Username Title'],
        ['mail_login_username_description', 'Test Username Description'],
        ['mail_login_email_only_title', 'Test Email Title'],
        ['mail_login_email_only_description', 'Test Email Description'],
        ['mail_login_password_only_description', 'Test Password Description'],
        ['mail_login_password_reset_username_title', 'Test Reset Username Title'],
        ['mail_login_password_reset_username_description', 'Test Reset Username Description'],
        ['mail_login_password_reset_email_only_title', 'Test Reset Email Title'],
        ['mail_login_password_reset_email_only_description', 'Test Reset Email Description'],
      ]);

    // Configure config mock to expect set() calls with correct values.
    $this->config->expects($this->exactly(13))
      ->method('set')
      ->willReturnCallback(function($key, $value) {
        // Verify the expected key-value pairs
        $expected_values = [
          'mail_login_enabled' => TRUE,
          'mail_login_case_sensitive' => FALSE,
          'mail_login_email_only' => TRUE,
          'mail_login_override_login_labels' => TRUE,
          'mail_login_username_title' => 'Test Username Title',
          'mail_login_username_description' => 'Test Username Description',
          'mail_login_email_only_title' => 'Test Email Title',
          'mail_login_email_only_description' => 'Test Email Description',
          'mail_login_password_only_description' => 'Test Password Description',
          'mail_login_password_reset_username_title' => 'Test Reset Username Title',
          'mail_login_password_reset_username_description' => 'Test Reset Username Description',
          'mail_login_password_reset_email_only_title' => 'Test Reset Email Title',
          'mail_login_password_reset_email_only_description' => 'Test Reset Email Description',
        ];
        
        $this->assertArrayHasKey($key, $expected_values);
        $this->assertEquals($expected_values[$key], $value);
        
        return $this->config;
      });

    // Expect save() to be called once.
    $this->config->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    // Call submitForm.
    $this->form->submitForm($form, $form_state);
  }

  /**
   * Test submitForm with minimal configuration values.
   */
  public function testSubmitFormWithMinimalValues() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    // Configure form state to return minimal values.
    $form_state->expects($this->any())
      ->method('getValue')
      ->willReturnMap([
        ['mail_login_enabled', FALSE],
        ['mail_login_case_sensitive', TRUE],
        ['mail_login_email_only', FALSE],
        ['mail_login_override_login_labels', FALSE],
        ['mail_login_username_title', ''],
        ['mail_login_username_description', ''],
        ['mail_login_email_only_title', ''],
        ['mail_login_email_only_description', ''],
        ['mail_login_password_only_description', ''],
        ['mail_login_password_reset_username_title', ''],
        ['mail_login_password_reset_username_description', ''],
        ['mail_login_password_reset_email_only_title', ''],
        ['mail_login_password_reset_email_only_description', ''],
      ]);

    // Configure config mock to expect set() calls with minimal values.
    $this->config->expects($this->exactly(13))
      ->method('set')
      ->willReturnCallback(function($key, $value) {
        // Verify the expected key-value pairs
        $expected_values = [
          'mail_login_enabled' => FALSE,
          'mail_login_case_sensitive' => TRUE,
          'mail_login_email_only' => FALSE,
          'mail_login_override_login_labels' => FALSE,
          'mail_login_username_title' => '',
          'mail_login_username_description' => '',
          'mail_login_email_only_title' => '',
          'mail_login_email_only_description' => '',
          'mail_login_password_only_description' => '',
          'mail_login_password_reset_username_title' => '',
          'mail_login_password_reset_username_description' => '',
          'mail_login_password_reset_email_only_title' => '',
          'mail_login_password_reset_email_only_description' => '',
        ];
        
        $this->assertArrayHasKey($key, $expected_values);
        $this->assertEquals($expected_values[$key], $value);
        
        return $this->config;
      });

    // Expect save() to be called once.
    $this->config->expects($this->once())
      ->method('save')
      ->willReturnSelf();

    // Call submitForm.
    $this->form->submitForm($form, $form_state);
  }

  /**
   * Test that form configuration integrity is maintained.
   */
  public function testFormConfigurationIntegrity() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    // Configure config mock to return null values (simulating missing config).
    $this->config->expects($this->any())
      ->method('get')
      ->willReturn(NULL);

    $result = $this->form->buildForm($form, $form_state);

    // Test that default values are used when config is missing.
    $this->assertNull($result['general']['mail_login_enabled']['#default_value']);
    $this->assertNull($result['general']['mail_login_case_sensitive']['#default_value']);
    $this->assertNull($result['general']['mail_login_email_only']['#default_value']);
    $this->assertNull($result['general']['mail_login_override_login_labels']['#default_value']);

    // Test that text fields have fallback values when config is missing.
    $this->assertStringContainsString('Log in by username/email address', $result['general']['mail_login_username_title']['#default_value']);
    $this->assertStringContainsString('You can use your username or email address to login', $result['general']['mail_login_username_description']['#default_value']);
  }

}
