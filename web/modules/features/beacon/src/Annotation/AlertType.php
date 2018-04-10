<?php

namespace Drupal\beacon\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Alert type item annotation object.
 *
 * @see \Drupal\beacon\Plugin\AlertTypeManager
 * @see plugin_api
 *
 * @Annotation
 */
class AlertType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
