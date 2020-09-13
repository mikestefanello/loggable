<?php

namespace Drupal\beacon;

use Drupal\beacon\Entity\EventInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\beacon\Plugin\AlertTypeManager;
use Drupal\Core\Path\PathMatcherInterface;

/**
 * Class AlertDispatcher.
 *
 * Dispatch alerts for events.
 */
class AlertDispatcher implements AlertDispatcherInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The alert type plugin manager.
   *
   * @var \Drupal\beacon\Plugin\AlertTypeManager
   */
  protected $alertTypeManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs a new AlertDispatcher object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\beacon\Plugin\AlertTypeManager $plugin_manager_alert_type
   *   The alert type plugin manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AlertTypeManager $plugin_manager_alert_type, PathMatcherInterface $path_matcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->alertTypeManager = $plugin_manager_alert_type;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public function dispatch(EventInterface $event) {
    // Load matching alerts.
    if ($alerts = $this->loadEventAlerts($event)) {
      // Iterate the alerts.
      foreach ($alerts as $alert) {
        // Dispatch the alert.
        $this->alertTypeManager
          ->createInstanceFromAlert($alert)
          ->send($event);
      }

      return count($alerts);
    }

    return FALSE;
  }

  /**
   * Load enabled alert entities that match a given event.
   *
   * @param \Drupal\beacon\Entity\EventInterface $event
   *   An event entity.
   *
   * @return array
   *   An array of Alert entities.
   */
  public function loadEventAlerts(EventInterface $event) {
    // Load alert storage.
    $storage = $this->entityTypeManager
      ->getStorage('alert');

    // Query to find the matching alerts.
    $ids = $storage
      ->getQuery()
      ->condition('enabled', 1)
      ->condition('channel', $event->getParent()->id())
      ->condition('event_severity', $event->getSeverity())
      ->execute();

    // Load the alerts.
    $alerts = $ids ? $storage->loadMultiple($ids) : [];

    // Iterate the alerts.
    foreach ($alerts as $index => $alert) {
      // Check if this alert has event type filters.
      if ($types = $alert->getEventTypes()) {
        // Check if the type is not a match.
        if (!$this->pathMatcher->matchPath($event->getType(), implode("\n", $types))) {
          // Remove this alert.
          unset($alerts[$index]);
        }
      }
    }

    return $alerts;
  }

}
