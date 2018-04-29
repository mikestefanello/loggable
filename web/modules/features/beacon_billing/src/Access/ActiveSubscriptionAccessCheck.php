<?php

namespace Drupal\beacon_billing\Access;

use Drupal\user\Entity\User;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\beacon_billing\BeaconBilling;
use Drupal\Core\Session\AccountInterface;

/**
 * Access check for an active subscription.
 */
class ActiveSubscriptionAccessCheck implements AccessInterface {

  /**
   * The beacon billing service.
   *
   * @var \Drupal\beacon_billing\BeaconBilling
   */
  protected $beaconBilling;

  /**
   * Constructs a ActiveSubscriptionAccessCheck object.
   *
   * @param \Drupal\beacon_billing\BeaconBilling $beacon_billing
   *   The beacon billing service.
   */
  public function __construct(BeaconBilling $beacon_billing) {
    $this->beaconBilling = $beacon_billing;
  }

  /**
   * Checks for an active subscription.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(AccountInterface $account) {
    // Load the user's subscription.
    $subscription = $this->beaconBilling->getUserSubscription(User::load($account->id()));

    // Access is granted if the user has no subscription or if the subscription
    // is not suspended.
    if (!$subscription || !$subscription->isSuspended()) {
      $access = AccessResult::allowed();
    }
    else {
      $access = AccessResult::forbidden();
    }

    // Cache per user.
    $access->cachePerUser();

    // Add the subscription as a cacheable dependency.
    if ($subscription) {
      $access->addCacheableDependency($subscription);
    }

    return $access;
  }

}
