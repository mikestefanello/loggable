<?php

namespace Drupal\beacon_billing\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for alert quotas.
 *
 * @Constraint(
 *   id = "AlertQuota",
 *   label = @Translation("Alert quota", context = "Validation"),
 *   type = "entity:alert"
 * )
 */
class AlertQuotaConstraint extends Constraint {

  public $message = 'You have reached your quota of @quota alerts for this channel.';

}
