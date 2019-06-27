<?php

declare(strict_types = 1);

namespace Drupal\cas_mock_server\Form;

use Drupal\cas_mock_server\ServiceTicketHelper;
use Drupal\cas_mock_server\UserManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a CAS mock server form.
 */
class LoginForm extends FormBase {

  /**
   * The mock user manager.
   *
   * @var \Drupal\cas_mock_server\UserManagerInterface
   */
  protected $userManager;

  /**
   * Constructs a login form for the mock CAS server.
   *
   * @param \Drupal\cas_mock_server\UserManagerInterface $userManager
   *   The mock user manager.
   */
  public function __construct(UserManagerInterface $userManager) {
    $this->userManager = $userManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cas_mock_server.user_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cas_mock_server_login';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $settings['email'],
      '#required' => TRUE,
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $settings['password'],
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $settings['submit'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $user = $this->getUser($form_state);

    if (empty($user)) {
      $form_state->setErrorByName('email', $this->t('Unrecognized user name or password.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->getUser($form_state);
    $service_ticket = ServiceTicketHelper::generateServiceTicket();
    $this->userManager->assignServiceTicket($user['username'], $service_ticket);
    $form_state->setRedirect('cas.service', ['ticket' => $service_ticket]);
  }

  /**
   * The title callback for the login form.
   *
   * @return string
   *   The form title.
   */
  public function title(): string {
    return $this->getSettings()['title'];
  }

  /**
   * Returns the settings for the login form.
   *
   * @return array
   *   The settings as an associative array.
   */
  protected function getSettings(): array {
    return $this->configFactory()->get('cas_mock_server.settings')->get('login_form');
  }

  /**
   * Returns the user data for the CAS user that logged in to the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array|null
   *   The user data, or NULL if no user matches the email address and password
   *   that were entered in the form.
   */
  protected function getUser(FormStateInterface $form_state): ?array {
    $email = $form_state->getValue('email');
    $password = $form_state->getValue('password');

    $users = $this->userManager->getUsersByAttributes([
      'email' => $email,
      'password' => $password,
    ]);

    if (count($users) === 1) {
      return reset($users);
    }

    return NULL;
  }

}
