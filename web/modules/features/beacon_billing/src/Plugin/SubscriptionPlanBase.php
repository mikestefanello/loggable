<?php

namespace Drupal\beacon_billing\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Subscription plan plugins.
 */
abstract class SubscriptionPlanBase extends PluginBase implements SubscriptionPlanInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function planInfoIncludes() {
    return [];
  }

}
