<?php

namespace Drupal\beacon\Routing;

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
    // Restrict API collection routes to authenticated users.
    foreach (beacon_entity_types() as $type) {
      if ($route = $collection->get("jsonapi.{$type}.collection")) {
        $route->setRequirement('_role', 'authenticated');
      }
    }

    // List the endpoints.
    $endpoints = [
      'collection',
      'individual',
      'related',
      'relationship',
    ];

    // Remove CSRF requirement and only allow key authentication for the beacon
    // entity type API routes.
    foreach (beacon_entity_types() as $type) {
      // Iterate the endpoints.
      foreach ($endpoints as $endpoint) {
        // Load the route.
        if ($route = $collection->get("jsonapi.{$type}.{$endpoint}")) {
          $requirements = $route->getRequirements();
          unset($requirements['_csrf_request_header_token']);
          $route->setRequirements($requirements);
          $route->setOption('_auth', ['key_auth']);
        }
      }
    }
  }

}
