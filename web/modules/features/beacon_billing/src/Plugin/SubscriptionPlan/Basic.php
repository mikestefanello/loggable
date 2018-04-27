<?php

namespace Drupal\beacon_billing\Plugin\SubscriptionPlan;

use Drupal\beacon_billing\Plugin\SubscriptionPlanBase;
use Drupal\Core\Render\Markup;

/**
 * Basic subscription plan.
 *
 * @SubscriptionPlan(
 *   id = "basic",
 *   label = @Translation("Basic"),
 *   planId = "basic",
 *   price = "3.00",
 *   period = "month",
 *   quotaEvents = 500,
 *   quotaAlerts = 3,
 *   eventHistory = 7,
 *   restrictedAlertTypes = {
 *     "text_message"
 *   }
 * )
 */
class Basic extends SubscriptionPlanBase {

  /**
   * {@inheritdoc}
   */
  public function planInfoIncludes() {
    return [
      // TODO: Should this be automated?
      $this->t('No text message alerts'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();

    // Load the plugin definition.
    $definition = $this->getPluginDefinition();

    // Load alert storage.
    $storage = $this->entityTypeManager
      ->getStorage('alert');

    // Query to find text message alerts the current user has.
    $results = $storage
      ->getQuery()
      ->condition('type', 'text_message')
      ->condition('user_id', $this->currentUser->id())
      ->execute();

    // Check if there are alerts.
    if ($results) {
      // Load the alerts.
      $alerts = $storage
        ->loadMultiple($results);

      // Iterate the alerts to generate an error message.
      $links = [];
      foreach ($alerts as $alert) {
        $links[] = $alert->link(NULL, 'edit-form');
      }

      // Generate an error message.
      $errors[] = $this->t('The following alerts use text messaging which the %plan plan does not allow: @alerts', [
        '%plan' => $definition['label'],
        '@alerts' => Markup::create(implode(', ', $links)),
      ]);
    }

    return $errors;
  }

}
