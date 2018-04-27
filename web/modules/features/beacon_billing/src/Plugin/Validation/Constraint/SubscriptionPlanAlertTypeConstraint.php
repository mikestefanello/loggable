<?php

namespace Drupal\beacon_billing\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for alert type based on the subscription plan.
 *
 * @Constraint(
 *   id = "SubscriptionPlanAlertType",
 *   label = @Translation("Subscription plan alert type", context = "Validation")
 * )
 */
class SubscriptionPlanAlertTypeConstraint extends Constraint {

  public $message = 'The %plan plan does not allow the selected alert type. Please upgrade your subscription plan in order to use it.';

}
