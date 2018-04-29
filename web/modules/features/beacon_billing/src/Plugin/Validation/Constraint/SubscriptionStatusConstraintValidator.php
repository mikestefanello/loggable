<?php

namespace Drupal\beacon_billing\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint validator for subscription status.
 */
class SubscriptionStatusConstraintValidator extends SubscriptionConstraintValidatorBase {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    // Load the subscription for the entity owner.
    if ($subscription = $this->beaconBilling->getUserSubscription($value->getOwner())) {
      // Check if the subscription is suspended.
      if ($subscription->isSuspended()) {
        // Add a violation.
        $this->context->buildViolation($constraint->message)
          ->addViolation();
      }
    }
  }

}
