<?php

namespace Drupal\cas_mock_server\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for the CAS mock server.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cas_mock_server_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cas_mock_server.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cas_mock_server.settings');

    $form['login_form'] = [
      '#type' => 'details',
      '#title' => $this->t('Login form'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['login_form']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form title'),
      '#description' => $this->t('The title to display on the login form.'),
      '#default_value' => $config->get('login_form.title'),
      '#required' => TRUE,
    ];
    $form['login_form']['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('E-mail field'),
      '#description' => $this->t('The label to use for the e-mail field.'),
      '#default_value' => $config->get('login_form.email'),
      '#required' => TRUE,
    ];
    $form['login_form']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password field'),
      '#description' => $this->t('The label to use for the password field.'),
      '#default_value' => $config->get('login_form.password'),
      '#required' => TRUE,
    ];
    $form['login_form']['submit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Submit button'),
      '#description' => $this->t('The submit button text.'),
      '#default_value' => $config->get('login_form.submit'),
      '#required' => TRUE,
    ];

    $form['users'] = [
      '#type' => 'details',
      '#title' => $this->t('Mock user management'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['users']['expire'] = [
      '#type' => 'number',
      '#title' => $this->t('Expiration time'),
      '#description' => $this->t('The time (in seconds) after which mock users will be automatically deleted.'),
      '#default_value' => $config->get('users.expire'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('cas_mock_server.settings')
      ->set('login_form.title', $form_state->getValue(['login_form', 'title']))
      ->set('login_form.email', $form_state->getValue(['login_form', 'email']))
      ->set('login_form.password', $form_state->getValue(['login_form', 'password']))
      ->set('login_form.submit', $form_state->getValue(['login_form', 'submit']))
      ->set('users.expire', $form_state->getValue(['users', 'expire']))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
