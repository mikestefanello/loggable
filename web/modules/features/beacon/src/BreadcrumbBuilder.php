<?php

namespace Drupal\beacon;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Define breadcrumb trails based on the beacon entity hierarchy.
 */
class BreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // Check if a beacon entity is present in the route.
    return (bool) $this->getRouteParameterEntity($route_match);
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    // Extract the entity being viewed.
    $entity = $this->getRouteParameterEntity($route_match);

    // Start the breadcrumb.
    $this->breadcrumb = new Breadcrumb();
    $this->breadcrumb->addCacheContexts(['url']);
    $this->breadcrumb->addCacheableDependency($entity);

    // Start the links.
    $links = [];

    // Add a link home.
    $links[] = Link::fromTextAndUrl(t('Home'), Url::fromRoute('<front>'));

    // Traverse the hierarchy upwards.
    $parent = $entity;
    while ($parent = $parent->getParent()) {
      // Add a link.
      $links[] = $parent->toLink();

      // Add the cache dependency.
      $this->breadcrumb->addCacheableDependency($parent);
    }

    // Set the links and return.
    return $this->breadcrumb->setLinks($links);
  }

  /**
   * Get a beacon entity from the route, if available.
   *
   * @param RouteMatchInterface $route_match
   *   The route match.
   * @return mixed|NULL
   *   The entity, if found, otherwise NULL.
   */
  public function getRouteParameterEntity(RouteMatchInterface $route_match) {
    // Iterate the types.
    foreach (beacon_entity_types() as $type) {
      // Check for the entity as a route parameter.
      if ($entity = $route_match->getParameter($type)) {
        if (!is_string($entity)) {
          return $entity;
        }
      }
    }

    return NULL;
  }

}
