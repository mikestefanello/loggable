<?php

namespace Drupal\beacon\Plugin\AlertType;

use Drupal\beacon\Plugin\AlertTypeBase;
use Drupal\beacon\Entity\EventInterface;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise;
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
  const TIMEOUT = 3;

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
      'channel' => $event->getParent()->uuid(),
      'channelName' => $event->getParent()->label(),
      'event' => $event->uuid(),
      'type' => $event->getType(),
      'severity' => $event->getSeverity(),
      'user' => $event->getUser(),
      'url' => ($url = $event->getUrl()) ? $url->toString() : NULL,
      'created' => $event->getCreatedTime(),
      'expire' => $event->getExpiration(),
      'message' => $event->getMessage(),
    ];

    // Queue the request.
    $this->queueHttpRequest('POST', $settings['endpoint'], [
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
      ],
      'json' => $data,
    ]);
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

  /**
   * Queue an HTTP request to be made during shutdown.
   *
   * All requests are queued and executed in a shutdown function asynchronously
   * so that the page response does not have to wait for the requests to finish.
   *
   * @param string $method
   *   The HTTP request method.
   * @param string $uri
   *   The HTTP request endpoint URI.
   * @param array $options
   *   An array of request options.
   */
  public function queueHttpRequest($method, $uri, array $options = []) {
    $requests = &drupal_static(__METHOD__, []);

    // Register a shutdown function if this is the first request to be queued.
    if (empty($requests)) {
      drupal_register_shutdown_function([$this, 'executeQueuedHttpRequests']);
    }

    // Merge in option detaults.
    $options = array_merge([
      'timeout' => self::TIMEOUT,
      'allow_redirects' => FALSE,
    ], $options);

    // Create and store the request.
    $requests[] = $this->httpClient->requestAsync($method, $uri, $options);
  }

  /**
   * Asynchronously execute all queued HTTP requests.
   *
   * @see queueHttpRequest()
   */
  public static function executeQueuedHttpRequests() {
    $requests = &drupal_static('Drupal\beacon\Plugin\AlertType\Webhook::queueHttpRequest', []);
    Promise\settle($requests)->wait();
  }

}
