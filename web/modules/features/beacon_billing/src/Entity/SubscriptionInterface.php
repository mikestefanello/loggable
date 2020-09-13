<?php

namespace Drupal\beacon_billing\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Subscription entities.
 *
 * @ingroup beacon_billing
 */
interface SubscriptionInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Subscription creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Subscription.
   */
  public function getCreatedTime();

  /**
   * Sets the Subscription creation timestamp.
   *
   * @param int $timestamp
   *   The Subscription creation timestamp.
   *
   * @return \Drupal\beacon_billing\Entity\SubscriptionInterface
   *   The called Subscription entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Determine if a Subscription has a credit card on file.
   *
   * @return bool
   *   TRUE if the subscription has a credit card, otherwise FALSE.
   */
  public function hasCard();

  /**
   * Get the credit card last 4 digits.
   *
   * @return int
   *   The last 4 digits of the credit card.
   */
  public function getCardLast4();

  /**
   * Get the Subscription status.
   *
   * @return string
   *   A subscription status constant.
   */
  public function getStatus();

  /**
   * Get the name.
   *
   * @return string
   *   The name on the subscription.
   */
  public function getName();

  /**
   * Get the email.
   *
   * @return string
   *   The email address.
   */
  public function getEmail();

  /**
   * Get the Subscription plan.
   *
   * @return string
   *   The subscription plan.
   */
  public function getPlan();

  /**
   * Determine if the subscription is suspended.
   *
   * Suspended means the functionality that is provided should be revoked.
   *
   * @return bool
   *   TRUE if the subscription is suspended, otherwise FALSE.
   */
  public function isSuspended();

  /**
   * Get the Subscription ID (not the entity ID).
   *
   * @return string
   *   A subscription ID.
   */
  public function getSubscriptionId();

  /**
   * Get the customer ID.
   *
   * @return string
   *   A customer ID.
   */
  public function getCustomerId();

}
