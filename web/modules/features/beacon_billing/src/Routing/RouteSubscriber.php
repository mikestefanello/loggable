<?php

namespace Drupal\beacon_billing\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Modify routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // List the endpoints.
    $endpoints = [
      'collection',
      'individual',
      'related',
      'relationship',
    ];

    // Iterate the entity types.
    foreach (beacon_entity_types() as $type) {
      // Iterate the endpoints.
      foreach ($endpoints as $endpoint) {
        // Load the route.
        if ($route = $collection->get("jsonapi.{$type}.{$endpoint}")) {
          // Add subscription requirements to the endpoints.
          $route->setRequirement('_active_subscription', 'TRUE');
        }
      }
    }
  }

}
