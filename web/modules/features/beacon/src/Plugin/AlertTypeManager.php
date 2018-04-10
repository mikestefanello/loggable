<?php

namespace Drupal\beacon\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Alert type plugin manager.
 */
class AlertTypeManager extends DefaultPluginManager {

  /**
   * Constructs a new AlertTypeManager object.
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
    parent::__construct('Plugin/AlertType', $namespaces, $module_handler, 'Drupal\beacon\Plugin\AlertTypeInterface', 'Drupal\beacon\Annotation\AlertType');

    $this->alterInfo('alert_type_info');
    $this->setCacheBackend($cache_backend, 'alert_type_plugins');
  }

}
