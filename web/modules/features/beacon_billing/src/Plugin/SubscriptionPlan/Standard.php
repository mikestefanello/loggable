<?php

namespace Drupal\beacon_billing\Plugin\SubscriptionPlan;

use Drupal\beacon_billing\Plugin\SubscriptionPlanBase;

/**
 * Standard subscription plan.
 *
 * @SubscriptionPlan(
 *   id = "standard",
 *   label = @Translation("Standard"),
 *   planId = "standard",
 *   price = "5.00",
 *   period = "month",
 *   quotaEvents = 3000,
 *   quotaAlerts = 10
 * )
 */
class Standard extends SubscriptionPlanBase {

}
