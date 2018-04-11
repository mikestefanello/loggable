<?php

namespace Drupal\beacon\Plugin\AlertType;

use Drupal\beacon\Plugin\AlertTypeBase;
use Drupal\beacon\Entity\EventInterface;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alert type plugin for sending alerts via webhook.
 *
 * @AlertType(
 *   id = "webhook",
 *   label = @Translation("Webhook"),
 * )
 */
class Webhook extends AlertTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The request timeout length, in seconds.
   *
   * @var int
   */
  const TIMEOUT = 10;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function send(EventInterface $event) {
    $settings = $this->getSettings();

    // Build the data to post.
    $data = [
      'channel' => $event->channel->entity->uuid(),
      'channelName' => $event->channel->entity->label(),
      'event' => $event->uuid(),
      'type' => $event->type->value,
      'severity' => $event->severity->value,
      'user' => $event->user->value,
      'url' => $event->url->first() ? $event->url->first()->getUrl()->toString() : '',
      'created' => $event->created->value,
      'expire' => $event->expire->value,
      'message' => $event->message->value,
    ];

    // Attempt the POST to the endpoint.
    try {
      $response = $this->httpClient->request('POST', $settings['endpoint'], [
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
        ],
        'json' => $data,
        'allow_redirects' => FALSE,
        'timeout' => self::TIMEOUT,
        'synchronous' => TRUE,
      ]);
    }
    catch (RequestException $e) {

    }
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
    $form['example'] = [
      '#type' => 'details',
      '#title' => t('Example request'),
      '#open' => FALSE,
    ];
    $form['example']['headers'] = [
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'h4',
        '#value' => $this->t('Headers'),
      ],
      'code' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#value' => "Accept: application/json\nContent-Type: application/json",
      ],
    ];
    $form['example']['payload'] = [
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'h4',
        '#value' => $this->t('Payload'),
      ],
      'code' => [
        '#type' => 'html_tag',
        '#tag' => 'pre',
        '#value' => '{
   "channel":"742caa49-19e9-4126-ad35-4089d3eee13b",
   "channelName":"Ecommerce store",
   "event":"821cda32-32e2-1423-sa32-4723d3asf33p",
   "type":"order",
   "severity":"notice",
   "user":"John Doe",
   "url":"http:\/\/mystore.com/cart",
   "created":1523451294,
   "expire":1524660416,
   "message":"A new order was completed for $100.00"
}',
      ],
    ];
    return $form;
  }

}
