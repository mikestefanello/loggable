<?php

namespace Drupal\beacon_billing\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Subscription plan item annotation object.
 *
 * @see \Drupal\beacon_billing\Plugin\SubscriptionPlanManager
 * @see plugin_api
 *
 * @Annotation
 */
class SubscriptionPlan extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The plan ID.
   *
   * @var string
   */
  public $planId;

  /**
   * The plan billing period.
   *
   * @var string
   */
  public $period;

  /**
   * The plan base base price.
   *
   * @var float
   */
  public $price;

  /**
   * The quota for events per channel.
   *
   * @var int
   */
  public $quotaEvents;

  /**
   * The quota for alerts per channel.
   *
   * @var int
   */
  public $quotaAlerts;

  /**
   * The history of events, in days.
   *
   * @var int
   */
  public $eventHistory;

}
