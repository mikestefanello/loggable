<?php

namespace Drupal\beacon_billing\Plugin;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;

/**
 * Base class for Subscription plan plugins.
 */
abstract class SubscriptionPlanBase extends PluginBase implements SubscriptionPlanInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructs the SubscriptionPlanBase object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param mixed $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user account.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountProxy $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function planInfoIncludes() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = [];

    // Load the plugin definition.
    $definition = $this->getPluginDefinition();

    // Check if there is an alert quota.
    if (!empty($definition['quotaAlerts'])) {
      // Query to find channels the current user has that have more alerts than
      // the quota of this plan. We do not care about events because they will
      // automatically expire.
      $results = $this->entityTypeManager
        ->getStorage('alert')
        ->getAggregateQuery('AND')
        ->conditionAggregate('channel', 'COUNT', $definition['quotaAlerts'], '>')
        ->groupBy('channel')
        ->condition('user_id', $this->currentUser->id())
        ->execute();

      // Extract the channel IDs.
      $channel_ids = [];
      foreach ($results as $result) {
        $channel_ids[] = $result['channel'];
      }

      // Check if there are channels.
      if ($channel_ids) {
        // Load the channels.
        $channels = $this->entityTypeManager
          ->getStorage('channel')
          ->loadMultiple($channel_ids);

        // Iterate the channels to generate an error message.
        $links = [];
        foreach ($channels as $channel) {
          $links[] = $channel->link();
        }

        // Generate an error message.
        $errors[] = $this->t('The following channels have more alerts than the %plan plan allows: @channels', [
          '%plan' => $definition['label'],
          '@channels' => Markup::create(implode(', ', $links)),
        ]);
      }
    }

    return $errors;
  }

}
