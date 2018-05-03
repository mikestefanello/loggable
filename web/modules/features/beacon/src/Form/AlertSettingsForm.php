<?php

namespace Drupal\beacon\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AlertSettingsForm.
 *
 * Provide settings for alert entities.
 */
class AlertSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'beacon.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'alert_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('beacon.settings');
    $form['alert_email_from'] = [
      '#type' => 'email',
      '#title' => $this->t('Email alert from address'),
      '#description' => $this->t('The email address that email alerts are sent from. Leave blank to default to the site email address.'),
      '#required' => FALSE,
      '#default_value' => $config->get('alert_email_from'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Save the config.
    $this->config('beacon.settings')
      ->set('alert_email_from', $form_state->getValue('alert_email_from'))
      ->save();
  }

}
