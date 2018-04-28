<?php

namespace Drupal\beacon;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides routes for Alert entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class AlertHtmlRouteProvider extends BeaconEntityHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    // Load the canonical route.
    $route = $collection->get('entity.alert.canonical');

    // Only allow admins to access since there is no need to view an alert.
    $route->setRequirement('_permission', $entity_type->getAdminPermission());

    return $collection;
  }

}
