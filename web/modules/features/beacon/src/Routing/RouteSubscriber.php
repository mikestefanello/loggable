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
    // List the endpoints.
    $endpoints = [
      'collection',
      'individual',
      'related',
      'relationship',
    ];

    // Remove CSRF requirement, require auth user role, only allow key
    // authentication for the beacon entity type API routes.
    foreach (beacon_entity_types() as $type) {
      // Iterate the endpoints.
      foreach ($endpoints as $endpoint) {
        // Load the route.
        if ($route = $collection->get("jsonapi.{$type}.{$endpoint}")) {
          $route->setRequirement('_role', 'authenticated');
          $requirements = $route->getRequirements();
          unset($requirements['_csrf_request_header_token']);
          $route->setRequirements($requirements);
          $route->setOption('_auth', ['key_auth']);
        }
      }
    }
  }

}
