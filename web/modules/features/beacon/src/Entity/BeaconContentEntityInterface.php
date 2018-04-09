<?php

namespace Drupal\beacon\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Beacon content entities.
 *
 * @ingroup beacon
 */
interface BeaconContentEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the entity name.
   *
   * @return string
   *   Name of the entity.
   */
  public function getName();

  /**
   * Sets the entity name.
   *
   * @param string $name
   *   The entity name.
   */
  public function setName($name);

  /**
   * Gets the creation timestamp.
   *
   * @return int
   *   Creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the creation timestamp.
   *
   * @param int $timestamp
   *   The creation timestamp.
   */
  public function setCreatedTime($timestamp);

  /**
   * Get the parent entity reference field name.
   *
   * @return string|NULL
   *   The entity reference field name or NULL if this entity does not have one.
   */
  public static function getParentReferenceFieldName();

  /**
   * Get hte parent entity reference entity type ID.
   *
   * This should automatically derive a value using getParentReferenceFieldName().
   *
   * @return string|NULL
   *   The parent entity reference target entity type ID, or NULL if there is not
   *   one defined.
   */
   public function getParentReferenceEntityTypeId();

  /**
   * Get the parent entity, if one is defined and present, either one or infinite
   * levels up the relationship tree.
   *
   * @param string|NULL $parent_entity_type
   *   The parent entity type to search for. If omitted, the type used in
   *   getParentReferenceFieldName() will be used which is the immediate parent
   *   of this entity. If you specific a different type, this function will look
   *   at parent's parent until the target entity type is found.
   * @return mixed|NULL
   *   The parent entity, if found, or NULL.
   */
  public function getParent($parent_entity_type = NULL);

}
