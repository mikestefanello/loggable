<?php

namespace Drupal\beacon;

use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Event entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class EventHtmlRouteProvider extends BeaconEntityHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    // Load the add form route.
    $route = $collection->get('entity.event.add_form');

    // Only allow admins to access since the API will be used to create events.
    $route->setRequirement('_permission', $entity_type->getAdminPermission());

    return $collection;
  }

}
