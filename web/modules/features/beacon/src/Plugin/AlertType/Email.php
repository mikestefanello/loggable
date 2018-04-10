<?php

namespace Drupal\beacon\Plugin\AlertType;

use Drupal\beacon\Plugin\AlertTypeBase;
use Drupal\beacon\Entity\EventInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Alert type plugin for sending alerts via email.
 *
 * @AlertType(
 *   id = "email",
 *   label = @Translation("Email"),
 * )
 */
class Email extends AlertTypeBase {

  /**
   * {@inheritdoc}
   */
  public function send(EventInterface $event) {
    // TODO
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    return [
      'email' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $form = [];
    $form['email'] = [
      '#type' => 'email',
      '#title' => t('Email address'),
      '#required' => TRUE,
      '#default_value' => $settings['email'],
      '#description' => t('The email address to send alerts to.'),
    ];
    return $form;
  }

}
