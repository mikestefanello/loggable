<?php

namespace Drupal\beacon_billing\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint validator for event quotas.
 */
class EventQuotaConstraintValidator extends QuotaConstraintValidatorBase {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    // Stop if the entity is not new.
    if (!$value->isNew()) {
      return;
    }

    // Stop if a channel has not yet been added.
    if (!$value->channel->entity) {
      return;
    }

    // Load the subscription plan for the entity owner.
    if ($plan = $this->beaconBilling->getUserSubscriptionPlanDefinition($value->getOwner())) {
      // Extract the quota for events.
      if ($quota = $plan['quotaEvents']) {
        // Get the entity count for this channel.
        $count = $this->entityTypeManager
          ->getStorage('event')
          ->getQuery()
          ->condition('user_id', $value->getOwnerId())
          ->condition('channel', $value->channel->entity->id())
          ->count()
          ->execute();

        // Check if the count exceeds the quota.
        if ($count >= $quota) {
          // Add a violation.
          $this->context->buildViolation($constraint->message)
            ->setParameter('@quota', $quota)
            ->addViolation();
        }
      }
    }
  }

}
