<?php

namespace Drupal\beacon_billing\EventSubscriber;

use Drupal\beacon_billing\BeaconBilling;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\stripe\Event\StripeEvents;
use Drupal\stripe\Event\StripeWebhookEvent;
use Stripe\Event as StripeEvent;

/**
 * Class StripeWebhookEventSubscriber.
 *
 * Subscribe to Stripe webhook.
 */
class StripeWebhookEventSubscriber implements EventSubscriberInterface {

  /**
   * The beacon billing service.
   *
   * @var \Drupal\beacon_billing\BeaconBilling
   */
  protected $beaconBilling;

  /**
   * Constructs a new StripeWebhookEventSubscriber object.
   *
   * @param \Drupal\beacon_billing\BeaconBilling $beacon_billing
   *   The beacon billing service.
   */
  public function __construct(BeaconBilling $beacon_billing) {
    $this->beaconBilling = $beacon_billing;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[StripeEvents::WEBHOOK][] = ['stripeWebhook', 1000];
    return $events;
  }

  /**
   * React to a Stripe webhook.
   *
   * @param \Drupal\stripe\Event\StripeWebhookEvent $event
   *   The Stripe event.
   */
  public function stripeWebhook(StripeWebhookEvent $event) {
    // Extract the Stripe event.
    $stripe_event = $event->getEvent();

    // Check the event type.
    switch ($stripe_event->type) {
      // Subscription updated.
      case StripeEvent::CUSTOMER_SUBSCRIPTION_UPDATED:
        // Extract the Stripe subscription.
        $stripe_subscription = $stripe_event->data->object;

        // Generate log parameters.
        $log_params = [
          '%hook' => $stripe_event->type,
          '%event_id' => $stripe_event->id,
          '%sub_id' => $stripe_subscription->id,
        ];

        // Load the subscription entity.
        if ($subscription = $this->beaconBilling->getSubscriptionById($stripe_subscription->id)) {
          // Update the status.
          $subscription
            ->set('status', $stripe_subscription->status)
            ->save();

          // Log the update.
          $this->beaconBilling->getLogger()->notice('Stripe webhook %hook (%event_id) updated subscription (%sub_id) with status %status.', $log_params + ['%status' => $stripe_subscription->status]);
        }
        else {
          // Log the error.
          $this->beaconBilling->error('Stripe webhook %hook (%event_id) provided subscription (%sub_id) that does not exist in Drupal.', $log_params);
        }
        break;
    }
  }

}
