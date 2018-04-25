<?php

namespace Drupal\beacon_billing\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for event quotas.
 *
 * @Constraint(
 *   id = "EventQuota",
 *   label = @Translation("Event quota", context = "Validation"),
 *   type = "entity:event"
 * )
 */
class EventQuotaConstraint extends Constraint {

  public $message = 'You have reached your quota of @quota events for this channel.';

}
