<?php

namespace Drupal\beacon_billing\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Subscription plan plugins.
 */
interface SubscriptionPlanInterface extends PluginInspectionInterface {

  /**
   * Return an array of plan information regarding what the plan includes.
   *
   * This is used to display information about the plan to the user.
   *
   * All quota information is already automatically included.
   *
   * @return array
   *   An array of strings.
   */
  public function planInfoIncludes();

  /**
   * Validate the current user switching to this plan.
   *
   * This is especially useful to prevent a user from downgrading if their
   * account currently has exceeded the chosen plan's limits.
   *
   * @return array
   *   An array of error messages to present to the user, if the validation has
   *   failed.
   */
  public function validate();

}
