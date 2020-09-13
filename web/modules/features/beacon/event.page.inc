<?php

/**
 * @file
 * Contains event.page.inc.
 *
 * Page callback for Event entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Component\Render\FormattableMarkup;
use Psr\Log\LogLevel;

/**
 * Prepares variables for Event templates.
 *
 * Default template: event.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_event(array &$variables) {
  // Fetch Event Entity Object.
  $event = $variables['elements']['#event'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  // Inline function to generate table rows.
  $add_row = function ($label, $value) {
    return [
      ['data' => $label, 'header' => TRUE],
      $value,
    ];
  };

  // Get the severity values.
  $severity_values = $event->getFieldDefinition('severity')
    ->getFieldStorageDefinition()
    ->getSetting('allowed_values');

  // Map severity classes.
  $severity_classes = [
    LogLevel::DEBUG => 'secondary',
    LogLevel::INFO => 'primary',
    LogLevel::NOTICE => 'secondary',
    LogLevel::WARNING => 'warning',
    LogLevel::ERROR => 'danger',
    LogLevel::CRITICAL => 'danger',
    LogLevel::ALERT => 'danger',
    LogLevel::EMERGENCY => 'danger',
  ];

  // Pick the severity class.
  $severity_class = $severity_classes[$event->getSeverity()];

  // Extract the event URL field value.
  $event_url = $event->getUrl();

  // Generate a link.
  $link = $event_url ? Link::fromTextAndUrl($event_url->toString(), $event_url->setOption('attributes', ['target' => '_blank'])) : NULL;

  // Extract the channel.
  $channel = $event->getParent();

  // Create a table to display the field values.
  $variables['table'] = [
    '#theme' => 'table',
    '#header' => [],
    '#rows' => [
      $add_row(t('Channel'), $channel ? $channel->link() : t('Deleted channel')),
      $add_row(t('Event ID'), $event->uuid()),
      $add_row(t('Type'), $event->getType()),
      $add_row(t('Severity'), new FormattableMarkup('<span class="badge badge-pill badge-@severity-class">@value</span>', ['@severity-class' => $severity_class, '@value' => $severity_values[$event->getSeverity()]])),
      $add_row(t('User'), $event->getUser()),
      $add_row(t('URL'), $link),
      $add_row(t('Date'), format_date($event->getCreatedTime(), 'long')),
      $add_row(t('Expires'), format_date($event->getExpiration(), 'long')),
      $add_row(t('Message'), $event->getMessage()),
    ],
  ];
}
