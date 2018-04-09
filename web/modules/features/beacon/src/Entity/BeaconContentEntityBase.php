<?php

namespace Drupal\beacon\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Content entity base class for Beacon entities.
 *
 * @ingroup beacon
 */
abstract class BeaconContentEntityBase extends ContentEntityBase implements BeaconContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    // Switch the entity ID out for the UUID.
    if (isset($uri_route_parameters[$this->getEntityTypeId()])) {
      $uri_route_parameters[$this->getEntityTypeId()] = $this->uuid();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['owner'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The user ID of owner of the entity.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setSetting('handler', 'default');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function getParentReferenceFieldName() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentReferenceEntityTypeId() {
    // Get the parent reference field name.
    if ($parent_field_name = $this->getParentReferenceFieldName()) {
      return $this->getFieldDefinition($parent_field_name)->getSetting('target_type');
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent($parent_entity_type = NULL) {
    // Get the parent reference field name.
    $parent_field_name = $this->getParentReferenceFieldName();

    // Stop if this entity does not have a parent.
    if (!$parent_field_name) {
      return NULL;
    }

    // Attempt to load the parent entity.
    if ($parent = $this->get($parent_field_name)->entity) {
      // Check if this is the correct type.
      if (!$parent_entity_type || ($parent->getEntityTypeId() == $parent_entity_type)) {
        return $parent;
      }

      // Continue searching.
      return $parent->getParent($parent_entity_type);
    }

    return NULL;
  }

}
