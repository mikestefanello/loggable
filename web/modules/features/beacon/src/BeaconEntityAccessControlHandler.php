<?php

namespace Drupal\beacon;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Access controller base for beacon entities.
 */
abstract class BeaconEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Check admin access.
    $admin = $this->userHasAdminPermission($account);

    // Check if the account is the owner of the entity.
    $is_owner = ($entity->getOwnerId() == $account->id());

    // Determine access.
    $access = $this->accessCondition($admin || $is_owner);

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
    // Allow authenticated users to create.
    return AccessResult::allowedIf($this->userHasRole($account, 'authenticated'))
      ->addCacheContexts(['user.roles']);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    // Always allow admin access.
    if ($this->userHasAdminPermission($account)) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Check if this field is admin-only.
    if ($field_definition->getSetting('admin_only')) {
      // Restrict access to admins.
      return AccessResult::forbidden()->cachePerPermissions();
    }

    return AccessResult::allowed();
  }

  /**
   * Helper function to return an access allowed or forbidden object.
   *
   * AccessResult::allowedIf() and AccessResult::forbiddenIf() can be tricky because
   * they fallback to ::neutral() whereas this is either allowed or forbidden.
   *
   * @param bool $condition
   *   The condition to check.
   * @return \Drupal\Core\Access\AccessResult
   *   An AccessResult set to allowed() if the condition is TRUE, otherwise set
   *   to forbidden().
   */
  public function accessCondition($condition) {
    return $condition ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Determine if a user has the admin permission of this entity type.
   *
   * @param Drupal\Core\Session\AccountInterface $account
   *   The account to check.
   * @return bool
   *   TRUE if the user has the admin permission, otherwiese FALSE.
   */
  public function userHasAdminPermission(AccountInterface $account) {
    return $account->hasPermission($this->getAdminPermission());
  }

  /**
   * Get the admin permission name for this entity type.
   *
   * @return string
   *   The admin permission name.
   */
  public function getAdminPermission() {
    return $this->entityType->getAdminPermission();
  }

  /**
   * Helper function to determine if a given user has a give role.
   *
   * @param Drupal\Core\Session\AccountInterface $account
   *   The account to check.
   * @param string $role
   *   The name of the role.
   * @return bool
   *   TRUE if the user has the role, otherwise FALSE.
   */
  public function userHasRole(AccountInterface $account, $role) {
    return $this->userLoad($account)->hasRole($role);
  }

  /**
   * Helper function to load a user entity from an account interface.
   *
   * @param Drupal\Core\Session\AccountInterface $account
   *   The account to load.
   * @return Drupal\user\Entity\User
   *   The account user entity.
   */
  public function userLoad(AccountInterface $account) {
    return User::load($account->id());
  }

}
