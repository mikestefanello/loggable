<?php

namespace Drupal\beacon;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
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
    // TODO.
    return FALSE;
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

    // Companies get a single breadcrumb.
    if ($entity->getEntityTypeId() == 'company') {
      return $this->breadcrumb->setLinks([Link::fromTextAndUrl(t('Home'), $entity->toUrl('canonical'))]);
    }

    // Start the links.
    $links = [];

    // Extract the entity label.
    $label = $entity->label();

    // Evaluations get custom breadcrumb labels.
    if ($entity->getEntityTypeId() == 'evaluation') {
      $label = t('Evaluation by %user', ['%user' => $entity->user_id->entity->getDisplayName()]);
    }

    // Add the entity.
    $links[] = Link::fromTextAndUrl($label, $entity->toUrl('canonical'));

    // Traverse the hierarchy upwards.
    $parent = $entity;
    while ($parent = $parent->getParent()) {
      // Add a link.
      $links[] = Link::fromTextAndUrl(($parent->getEntityTypeId() != 'company') ? $parent->label() : t('Home'), $parent->toUrl('canonical'));

      // Add the cache dependency.
      $this->breadcrumb->addCacheableDependency($parent);
    }

    // Reverse the links.
    $links = array_reverse($links);

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
    foreach (rex_entity_types() as $type) {
      // Check for the entity as a route parameter.
      if ($entity = $route_match->getParameter($type)) {
        if (!is_string($entity)) {
          return $entity;
        }
      }
    }

    // Iterate the types again.
    foreach (rex_entity_types() as $type) {
      // Check if this is an add form.
      if ($route_match->getRouteName() == "entity.{$type}.add_form") {
        // Check if there is a contextual entity to use.
        if ($entity = $this->rex->getContextualEntity($type)) {
          return $entity;
        }
      }
    }

    return $entity = NULL;
  }

}
