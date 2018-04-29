<?php

namespace Drupal\beacon_billing\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for subscription status.
 *
 * @Constraint(
 *   id = "SubscriptionStatus",
 *   label = @Translation("Subscription status", context = "Validation")
 * )
 */
class SubscriptionStatusConstraint extends Constraint {

  public $message = 'You subscription is currently inactive. Please visit the billing page and reactivate your subscription.';

}
