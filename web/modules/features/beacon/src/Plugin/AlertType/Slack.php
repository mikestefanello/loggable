<?php

namespace Drupal\beacon\Plugin\AlertType;

use Drupal\beacon\Entity\EventInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;

/**
 * Alert type plugin for sending alerts via Slack.
 *
 * @AlertType(
 *   id = "slack",
 *   label = @Translation("Slack"),
 * )
 */
class Slack extends Webhook {

  /**
   * Maximum event message length.
   *
   * @var int
   */
  const MESSAGE_LENGTH = 500;

  /**
   * {@inheritdoc}
   */
  public function send(EventInterface $event) {
    $settings = $this->getSettings();

    // Extract the message.
    $message = $event->message->value;

    // Check if the message needs to be trimmed.
    if (strlen($message) > SELF::MESSAGE_LENGTH) {
      // Trim the message.
      $message = substr($message, 0, SELF::MESSAGE_LENGTH) . '...';
    }

    // Build the Slack data.
    $data = [
      'channel' => $settings['channel'],
      'username' => $settings['username'],
      'attachments' => [
        [
          'pretext' => $this->t('A notification was dispatched from Loggable'),
          'author_name' => \Drupal::config('system.site')->get('name'),
          'author_link' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
          'title' => strip_tags($event->label()),
          'title_link' => $event->toUrl('canonical', ['absolute' => TRUE])->toString(),
          'fields' => [
            [
              'title' => $this->t('Channel'),
              'value' => $event->channel->entity->label(),
              'short' => TRUE,
            ],
            [
              'title' => $this->t('Type'),
              'value' => $event->type->value,
              'short' => TRUE,
            ],
            [
              'title' => $this->t('Severity'),
              'value' => $event->severity->value,
              'short' => TRUE,
            ],
            [
              'title' => $this->t('User'),
              'value' => $event->user->value,
              'short' => TRUE,
            ],
            [
              'title' => $this->t('Message'),
              'value' => $message,
              'short' => FALSE,
            ],
          ],
        ],
      ],
    ];

    // Queue the request.
    $this->queueHttpRequest('POST', $settings['endpoint'], [
      'body' => Json::encode($data),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    return [
      'endpoint' => '',
      'channel' => '',
      'username' => 'loggable',
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
      '#title' => t('Webhook URL'),
      '#required' => TRUE,
      '#default_value' => $settings['endpoint'],
      '#description' => t('The Slack webhook URL. This must being with https://hooks.slack.com.'),
    ];
    $form['channel'] = [
      '#type' => 'textfield',
      '#title' => t('Channel'),
      '#required' => TRUE,
      '#default_value' => $settings['channel'],
      '#description' => t('The Slack channel to post the message in. This must start with # for a room or @ for a direct message.'),
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#required' => TRUE,
      '#default_value' => $settings['username'],
      '#description' => t('The username to post the message as. This name does not have to exist in your Slack organization.'),
    ];
    $form['setup'] = [
      '#type' => 'details',
      '#title' => t('Slack webhook setup instructions'),
      '#open' => FALSE,
    ];
    $form['setup']['list'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('In the left sidebar, click on "Apps". You may need to be an administrator to access this.'),
        $this->t('Search for the "incoming-webhook" application and press "Install".'),
        $this->t('Copy the Webhook URL in to the field here.'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSettingsForm(FormStateInterface $form_state) {
    // Extract the settings.
    $settings = $form_state->getValue('settings_form');

    // Make sure the channel starts with a # or @.
    if (!in_array(substr($settings['channel'], 0, 1), ['#', '@'])) {
      $form_state->setErrorByName('settings_form][channel', $this->t('The Slack channel must start with a # or @.'));
    }

    // Check the webhook URL.
    if (substr($settings['endpoint'], 0, 23) != 'https://hooks.slack.com') {
      $form_state->setErrorByName('settings_form][endpoint', $this->t('The Slack webhook URL must begin with https://hooks.slack.com.'));
    }
  }

}
