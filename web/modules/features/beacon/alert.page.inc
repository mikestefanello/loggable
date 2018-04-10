<?php

/**
 * @file
 * Contains alert.page.inc.
 *
 * Page callback for Alert entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Alert templates.
 *
 * Default template: alert.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_alert(array &$variables) {
  // Fetch Alert Entity Object.
  $alert = $variables['elements']['#alert'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
