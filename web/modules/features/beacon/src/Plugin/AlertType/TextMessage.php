<?php

namespace Drupal\beacon\Plugin\AlertType;

use Drupal\beacon\Entity\EventInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Alert type plugin for sending alerts via text message.
 *
 * @AlertType(
 *   id = "text_message",
 *   label = @Translation("Text message"),
 * )
 */
class TextMessage extends Webhook {

  /**
   * {@inheritdoc}
   */
  public function send(EventInterface $event) {
    $settings = $this->getSettings();

    // TODO.
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    return [
      'number' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $form = [];
    $form['number'] = [
      '#type' => 'textfield',
      '#title' => t('Phone number'),
      '#required' => TRUE,
      '#default_value' => $settings['number'],
      '#description' => t('The phone number to send a text message to.'),
    ];
    // TODO.
    return $form;
  }

}
