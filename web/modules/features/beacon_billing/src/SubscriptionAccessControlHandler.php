<?php

namespace Drupal\beacon_billing;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Access controller for the Subscription entity.
 *
 * @see \Drupal\beacon_billing\Entity\Subscription.
 */
class SubscriptionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Check admin permissions.
    $access = AccessResult::allowedIfHasPermission($account, 'administer subscription entities');

    // Check for an update operation.
    if ($operation == 'update') {
      // Check if the user is not an admin.
      if ($access->isForbidden()) {
        // Check if the subscripton owner and user matches.
        if ($entity->getOwnerId() == $account->id()) {
          // Grant access.
          $access = AccessResult::allowed();
        }
      }
    }

    // Add caching.
    $access
      ->cachePerUser()
      ->addCacheableDependency($entity);

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer subscription entities');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    // Check if this field is admin-only.
    if ($field_definition->getSetting('admin_only')) {
      // Restrict access to admins.
      return AccessResult::allowedIfHasPermission($account, 'administer subscription entities');
    }

    return AccessResult::allowed();
  }

}
