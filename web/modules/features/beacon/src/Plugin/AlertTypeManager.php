<?php

namespace Drupal\beacon\Plugin;

use Drupal\beacon\Entity\AlertInterface;
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

  /**
   * Create a plugin instance from an alert entity.
   *
   * This will automatically pass in the proper configuration stored in the
   * entity which is provided via the settings forms.
   *
   * @param \Drupa\beacon\Entity\AlertInterface $alert
   *   An alert entity.
   * @param $plugin_id
   *   The plugin ID, or NULL to load the type from the Alert.
   * @return \Drupal\beacon\Plugin\AlertTypeInterface
   *   An alert type plugin instance.
   */
  public function createInstanceFromAlert(AlertInterface $alert, $plugin_id = NULL) {
    $settings = [];

    // Determine the plugin ID.
    $plugin_id = $plugin_id ? $plugin_id : $alert->get('type')->value;

    // Check if there are alert settings.
    if ($value = $alert->get('settings')->value) {
      // Unserialize the settings.
      $value = unserialize($value);

      // Check if this plugin has settings stored.
      if (isset($value[$plugin_id])) {
        // Use these settings.
        $settings = $value[$plugin_id];
      }
    }

    // Create an instance.
    return $this->createInstance($plugin_id, $settings);
  }

}
