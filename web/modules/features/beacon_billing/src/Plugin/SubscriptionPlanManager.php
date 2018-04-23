<?php

namespace Drupal\beacon_billing\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Subscription plan plugin manager.
 */
class SubscriptionPlanManager extends DefaultPluginManager {

  /**
   * Constructs a new SubscriptionPlanManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/SubscriptionPlan', $namespaces, $module_handler, 'Drupal\beacon_billing\Plugin\SubscriptionPlanInterface', 'Drupal\beacon_billing\Annotation\SubscriptionPlan');

    $this->alterInfo('subscription_plan_info');
    $this->setCacheBackend($cache_backend, 'subscription_plan_plugins');
  }

}
