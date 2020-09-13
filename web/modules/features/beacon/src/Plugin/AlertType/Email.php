<?php

namespace Drupal\beacon\Plugin\AlertType;

use Drupal\beacon\Plugin\AlertTypeBase;
use Drupal\beacon\Entity\EventInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alert type plugin for sending alerts via email.
 *
 * @AlertType(
 *   id = "email",
 *   label = @Translation("Email"),
 * )
 */
class Email extends AlertTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The mail sender service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MailManagerInterface $mail_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function send(EventInterface $event) {
    $settings = $this->getSettings();

    // Build the message parameters.
    $params = [
      'subject' => $this->t('Alert notification for channel @channel', ['@channel' => $event->getParent()->label()]),
      'body' => [
        $this->t('The following event has been logged:'),
        '',
        $this->t('Channel: @channel', ['@channel' => $event->getParent()->label()]),
        $this->t('Channel ID: @uuid', ['@uuid' => $event->getParent()->uuid()]),
        $this->t('Event ID: @uuid', ['@uuid' => $event->uuid()]),
        $this->t('Type: @type', ['@type' => $event->getType()]),
        $this->t('Severity: @severity', ['@severity' => $event->getSeverity()]),
        $this->t('User: @user', ['@user' => $event->getUser()]),
        $this->t('URL: @url', ['@url' => ($url = $event->getUrl()) ? $url->toString() : '']),
        $this->t('Date: @date', ['@date' => format_date($event->getCreatedTime(), 'long')]),
        $this->t('Expires: @date', ['@date' => format_date($event->getExpiration(), 'long')]),
        $this->t('Message: @message', ['@message' => $event->getMessage()]),
        '',
        $this->t('You can view this event here: @link', ['@link' => $event->url('canonical', ['absolute' => TRUE])]),
        '',
        $this->t('Please log if you wish to change your alerts: @link', ['@link' => Url::fromRoute('<front>')->setAbsolute()->toString()]),
      ],
    ];

    // Send the email.
    // TODO; Properly determine the language?
    $this->mailManager->mail('beacon', 'alert', $settings['email'], 'en', $params);
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
