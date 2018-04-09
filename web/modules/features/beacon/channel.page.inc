<?php

/**
 * @file
 * Contains channel.page.inc.
 *
 * Page callback for Channel entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Channel templates.
 *
 * Default template: channel.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_channel(array &$variables) {
  // Fetch Channel Entity Object.
  $channel = $variables['elements']['#channel'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
