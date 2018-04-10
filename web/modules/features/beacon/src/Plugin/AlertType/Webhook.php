<?php

namespace Drupal\beacon\Plugin\AlertType;

use Drupal\beacon\Plugin\AlertTypeBase;
use Drupal\beacon\Entity\EventInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Alert type plugin for sending alerts via webhook.
 *
 * @AlertType(
 *   id = "webhook",
 *   label = @Translation("Webhook"),
 * )
 */
class Webhook extends AlertTypeBase {

  /**
   * {@inheritdoc}
   */
  public function send(EventInterface $event) {

  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    return [
      'endpoint' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $form = [];
    $form['endpoint'] = [
      '#type' => 'url',
      '#title' => t('Endpoint'),
      '#required' => TRUE,
      '#default_value' => $settings['endpoint'],
      '#description' => t('The endpoint URL to POST the event data to.'),
    ];
    return $form;
  }

}
