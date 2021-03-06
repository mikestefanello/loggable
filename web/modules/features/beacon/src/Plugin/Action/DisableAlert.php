<?php

namespace Drupal\beacon\Plugin\Action;

use Drupal\Core\Action\Plugin\Action\EntityActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an action for disabling alert entities.
 *
 * @Action(
 *   id = "alert_disable_action",
 *   label = @Translation("Disable alert"),
 *   type = "alert"
 * )
 */
class DisableAlert extends EntityActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->setDisabled()->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->enabled->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
