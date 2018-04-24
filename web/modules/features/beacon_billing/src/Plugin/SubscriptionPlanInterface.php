<?php

namespace Drupal\beacon_billing\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Subscription plan plugins.
 */
interface SubscriptionPlanInterface extends PluginInspectionInterface {

  /**
   * Return an array of additional plan information regarding what the plan includes.
   *
   * This is used to display information about the plan to the user.
   *
   * All quota information is already automatically included.
   *
   * @return array
   *   An array of strings.
   */
  public function planInfoIncludes();

}
