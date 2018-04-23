<?php

namespace Drupal\beacon_billing\Plugin\SubscriptionPlan;

use Drupal\beacon_billing\Plugin\SubscriptionPlanBase;

/**
 * Advanced subscription plan.
 *
 * @SubscriptionPlan(
 *   id = "advanced",
 *   label = @Translation("Advanced"),
 *   planId = "advanced",
 *   price = "8.00",
 *   period = "month",
 *   quotaEvents = 10000,
 *   quotaAlerts = 20,
 *   eventHistory = 30
 * )
 */
class Advanced extends SubscriptionPlanBase {

}
