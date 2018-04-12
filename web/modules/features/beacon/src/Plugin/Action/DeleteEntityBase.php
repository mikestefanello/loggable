<?php

namespace Drupal\beacon\Plugin\Action;

use Drupal\Core\Action\Plugin\Action\EntityActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a base class for entity delete actions.
 */
abstract class DeleteEntityBase extends EntityActionBase {

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $this->entityTypeManager
      ->getDefinition($this->getPluginDefinition()['type'])
      ->delete($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple([$object]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('delete', $account, $return_as_object);
  }

}
