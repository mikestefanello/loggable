<?php

namespace Drupal\beacon\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Beacon entity' condition.
 *
 * @Condition(
 *   id = "beacon_entity",
 *   label = @Translation("Beacon entity"),
 * )
 */
class BeaconEntity extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a BeaconEntity condition plugin.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(RouteMatchInterface $route_match, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('current_route_match'),
      $configuration,
      $plugin_id,
      $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['types' => []] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Beacon entity types'),
      '#default_value' => $this->configuration['types'],
      '#options' => [
        'channel' => $this->t('Channel'),
        'event' => $this->t('Event'),
        'alert' => $this->t('Alert'),
      ],
      '#description' => $this->t('Specify which beacon entity types you want this block to appear on. It will only show on the canonical path.'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['types'] = array_filter($form_state->getValue('types'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $types = implode(', ', $this->configuration['types']);
    return $this->t('Return true on the following entity canonical paths: @types', ['@types' => $types]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // Get the route name.
    $route_name = $this->routeMatch->getRouteName();

    // Iterate the active entity types.
    foreach ($this->configuration['types'] as $type) {
      // Check for a canonical match.
      if ($route_name == "entity.{$type}.canonical") {
        return $this->isNegated() ? FALSE : TRUE;
      }
    }

    return $this->isNegated() ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url.path';
    return $contexts;
  }

}
