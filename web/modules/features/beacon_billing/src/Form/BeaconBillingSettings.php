<?php

namespace Drupal\beacon_billing\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BeaconBillingSettings.
 */
class BeaconBillingSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'beacon_billing.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'beacon_billing_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('beacon_billing.settings');
    $form['trial_period_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Free trial period (in days)'),
      '#description' => $this->t('Enter the amount of days the free trial period is for.'),
      '#default_value' => $config->get('trial_period_days'),
      '#required' => TRUE,
      '#min' => 0,
    ];
    $form['alert_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Alert email address'),
      '#description' => $this->t('Enter an email address to be alerted whenever there are billing or subscription errors.'),
      '#default_value' => $config->get('alert_email'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Save the config.
    $this->config('beacon_billing.settings')
      ->set('trial_period_days', $form_state->getValue('trial_period_days'))
      ->set('alert_email', $form_state->getValue('alert_email'))
      ->save();
  }

}
