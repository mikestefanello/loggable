<?php

namespace Drupal\beacon;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides routes for Beacon entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class BeaconEntityHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    // Switch the UUID.
    foreach (['canonical', 'edit_form', 'delete_form'] as $link) {
      $route = $collection->get("entity.{$entity_type_id}.{$link}");
      $route->setOption('parameters', [$entity_type_id => ['type' => 'entity_uuid', 'entity_type_id' => $entity_type_id]]);
      $route->setRequirement($entity_type_id, '[\d\w\-]+');
    }

    return $collection;
  }

}
