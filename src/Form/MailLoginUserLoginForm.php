<?php

namespace Drupal\mail_login\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BareHtmlPageRendererInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\user\Form\UserLoginForm;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a user login form that works with username or/and email.
 *
 * @internal
 */
class MailLoginUserLoginForm extends UserLoginForm {

  /**
   * The factory for configuration objects.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a MailLoginUserLoginForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterfac $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\user\UserFloodControlInterface $user_flood_control
   *   The user flood control service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication object.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Render\BareHtmlPageRendererInterface $bare_html_renderer
   *   The renderer.
   */
  public function __construct(ConfigFactoryInterface $config_factory, $user_flood_control, UserStorageInterface $user_storage, UserAuthInterface $user_auth, RendererInterface $renderer, BareHtmlPageRendererInterface $bare_html_renderer = NULL) {
    $this->configFactory = $config_factory;
    parent::__construct($user_flood_control, $user_storage, $user_auth, $renderer, $bare_html_renderer);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('user.flood_control'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('user.auth'),
      $container->get('renderer'),
      $container->get('bare_html_page_renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateAuthentication(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('mail_login.settings');

    $password = trim($form_state->getValue('pass'));
    $flood_config = $this->config('user.flood');
    if (!$form_state->isValueEmpty('name') && strlen($password) > 0) {
      // Do not allow any login from the current user's IP if the limit has been
      // reached. Default is 50 failed attempts allowed in one hour. This is
      // independent of the per-user limit to catch attempts from one IP to log
      // in to many different user accounts.  We have a reasonably high limit
      // since there may be only one apparent IP for all users at an institution.
      if (!$this->userFloodControl->isAllowed('user.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
        $form_state->set('flood_control_triggered', 'ip');
        return;
      }

      $account = NULL;
      // If mail login is enabled, check mail address.
      if ($config->get('mail_login_enabled')) {
        if (filter_var($form_state->getValue('name'), FILTER_VALIDATE_EMAIL)) {
          $accounts = $this->userStorage->loadByProperties([
            'mail' => $form_state->getValue('name'),
            'status' => 1,
          ]);
          $account = reset($accounts);
        }
      }
      // If no user was found using the email, and username login is enabled,
      // check username.
      if (empty($account) && !$config->get('mail_login_email_only')) {
        $accounts = $this->userStorage->loadByProperties([
          'name' => $form_state->getValue('name'),
          'status' => 1,
        ]);
        $account = reset($accounts);
      }

      if ($account) {
        if ($flood_config->get('uid_only')) {
          // Register flood events based on the uid only, so they apply for any
          // IP address. This is the most secure option.
          $identifier = $account->id();
        }
        else {
          // The default identifier is a combination of uid and IP address. This
          // is less secure but more resistant to denial-of-service attacks that
          // could lock out all users with public user names.
          $identifier = $account->id() . '-' . $this->getRequest()->getClientIP();
        }
        $form_state->set('flood_control_user_identifier', $identifier);

        // Don't allow login if the limit for this user has been reached.
        // Default is to allow 5 failed attempts every 6 hours.
        if (!$this->userFloodControl->isAllowed('user.failed_login_user', $flood_config->get('user_limit'), $flood_config->get('user_window'), $identifier)) {
          $form_state->set('flood_control_triggered', 'user');
          return;
        }
      }
      // We are not limited by flood control, so try to authenticate.
      // Store $uid in form state as a flag for self::validateFinal().
      $uid = $this->userAuth->authenticate($form_state->getValue('name'), $password);
      $form_state->set('uid', $uid);
    }
  }
}
