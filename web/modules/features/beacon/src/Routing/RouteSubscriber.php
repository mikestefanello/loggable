<?php

namespace Drupal\beacon\Routing;

use Drupal\user\Entity\User;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
  }

}
