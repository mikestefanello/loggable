<?php

namespace Drupal\beacon_billing\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint validator for alert type based on the subscription plan.
 */
class SubscriptionPlanAlertTypeConstraintValidator extends SubscriptionConstraintValidatorBase {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    // Stop if the value was not yet set.
    if ($value->isEmpty()) {
      return;
    }

    // Load the subscription plan for the entity owner.
    if ($plan = $this->beaconBilling->getUserSubscriptionPlanDefinition()) {
      // Check if there are restricted alert types.
      if ($plan['restrictedAlertTypes']) {
        // Check if this type is restricted.
        if (in_array($value->value, $plan['restrictedAlertTypes'])) {
          // Add a violation.
          $this->context->buildViolation($constraint->message)
            ->setParameter('%plan', $plan['label'])
            ->addViolation();
        }
      }
    }
  }

}
