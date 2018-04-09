<?php

namespace Drupal\beacon_ui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\user\Entity\User;

/**
 * Class SupportForm.
 */
class SupportForm extends FormBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The mail sender service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new SupportForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail sender service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailManagerInterface $mail_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->configFactory = $config_factory;
    $this->mailManager = $mail_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.mail'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'support_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load the current user.
    $user = User::load($this->currentUser()->id());

    $form['info'] = [
      '#type' => 'details',
      '#title' => $this->t('Your information'),
      '#open' => TRUE,
    ];
    $form['info']['name'] = [
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'h5',
        '#value' => $this->t('Name'),
      ],
      'value' => [
        '#type' => 'item',
        '#markup' => $user->getDisplayName(),
      ],
    ];
    $form['info']['email'] = [
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'h5',
        '#value' => $this->t('Email'),
      ],
      'value' => [
        '#type' => 'item',
        '#markup' => $user->mail->value,
      ],
    ];
    $form['request'] = [
      '#type' => 'details',
      '#title' => $this->t('Message'),
      '#open' => TRUE,
    ];
    $form['request']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#required' => TRUE,
    ];
    $form['request']['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
    ];
    $form['actions'] = [
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Submit support request'),
      ],
    ];
    $form['#cache'] = [
      'contexts' => [
        'user',
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Load the current user.
    $user = User::load($this->currentUser()->id());

    // Add the message parameters.
    $params = [
      'name' => $user->getDisplayName(),
      'email' => $user->mail->value,
      'subject' => $form_state->getValue('subject'),
      'message' => $form_state->getValue('message'),
    ];

    // Add the log parameters.
    $log_params = [
      '%uid' => $user->id(),
    ];

    // Load the site email address.
    $to = $this->configFactory->get('system.site')->get('mail');

    // Send the email.
    $result = $this->mailManager->mail('beacon_ui', 'support', $to, 'en', $params);

    // Alert the user.
    if ($result['result'] == TRUE) {
      drupal_set_message(t('Your support request has been submitted. A representative will contact you shortly.'));
      $this->loggerFactory->get('beacon_ui')->notice('Support request submitted from user %uid', $log_params);
    }
    else {
      drupal_set_message(t('There was a problem sending your message. Please try again or contact us directly at %to.', ['%to' => $to]), 'error');
      $this->loggerFactory->get('beacon_ui')->error('Support request failed to send from user %uid', $log_params);
    }
  }

}
