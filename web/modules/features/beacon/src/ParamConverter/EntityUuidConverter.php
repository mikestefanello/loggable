<?php

namespace Drupal\beacon\ParamConverter;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Class EntityUuidConverter.
 *
 * Parameter converter for entity UUIDs.
 */
class EntityUuidConverter implements ParamConverterInterface {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Creates a new EntityUuidConverter instance.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityRepositoryInterface $entity_repository) {
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    // Load the entity.
    return $this
      ->entityRepository
      ->loadEntityByUuid($definition['entity_type_id'], $value);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (
      !empty($definition['type']) &&
      !empty($definition['entity_type_id']) &&
      ($definition['type'] == 'entity_uuid')
    );
  }

}
