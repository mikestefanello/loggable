<?php

namespace Drupal\beacon_billing\Plugin\SubscriptionPlan;

use Drupal\beacon_billing\Plugin\SubscriptionPlanBase;

/**
 * Basic subscription plan.
 *
 * @SubscriptionPlan(
 *   id = "basic",
 *   label = @Translation("Basic"),
 *   planId = "basic",
 *   price = "3.00",
 *   period = "month",
 *   quotaEvents = 500,
 *   quotaAlerts = 3
 * )
 */
class Basic extends SubscriptionPlanBase {

}
