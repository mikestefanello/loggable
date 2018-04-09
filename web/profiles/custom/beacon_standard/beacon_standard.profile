<?php

use Drupal\Core\Config\FileStorage;

/**
 * Implements hook_install_tasks().
 */
function beacon_standard_install_tasks(&$install_state) {
  $tasks = [];
  $tasks['beacon_standard_enable_features_batch'] = [
    'display_name' => t('Install features'),
    'type' => 'batch',
  ];
  return $tasks;
}

/**
 * Install task callback to provide a batch to enable all features.
 *
 * @see beacon_standard_install_tasks().
 */
function beacon_standard_enable_features_batch() {
  // List features to install.
  $features = [
    // TODO
  ];

  // Create a batch operation.
  $batch = [
    'title' => t('Installing features'),
    'operations' => [],
  ];

  // Add an operation for each feature.
  foreach ($features as $feature) {
    $batch['operations'][] = ['beacon_standard_enable_feature', [$feature]];
  }

  return $batch;
}

/**
 * Batch operation callback to enable a feature module.
 *
 * This is done at the very end because existing configuration needs to be purged.
 */
function beacon_standard_enable_feature($feature, &$context = []) {
  // Load the configuration factory.
  $config_factory = \Drupal::configFactory();

  // Load a list of all active config.
  $configs = $config_factory->listAll();

  // Load the config files.
  $source = new FileStorage(drupal_get_path('module', $feature) . '/config/install');

  // Iterate the config files.
  foreach ($source->listAll() as $name) {
    // Check if this configuration exists.
    if (in_array($name, $configs)) {
      // Delete the configuration so this feature can override it.
      $config_factory->getEditable($name)->delete();
    }
  }

  // Install the features.
  \Drupal::service('module_installer')->install([$feature]);
}
