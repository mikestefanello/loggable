<?php

namespace Drupal\beacon_billing\EventSubscriber;

use Drupal\beacon_billing\BeaconBilling;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class SuspendedEventSubscriber.
 *
 * Redirect users to the suspended page if their company is suspended.
 */
class SuspendedEventSubscriber implements EventSubscriberInterface {

  /**
   * The beacon billing service.
   *
   * @var \Drupal\beacon_billing\BeaconBilling
   */
  protected $beaconBilling;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new SuspendedEventSubscriber object.
   *
   * @param \Drupal\beacon_billing\BeaconBilling $beacon_billing
   *   The beacon billing service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(BeaconBilling $beacon_billing, RequestStack $request_stack) {
    $this->beaconBilling = $beacon_billing;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['suspendedRedirect', 1000];
    return $events;
  }

  /**
   * Perform the user redirection, if suspended.
   */
  public function suspendedRedirect(FilterResponseEvent $event) {
    // Get the requested URI.
    $requested_uri = $this->requestStack->getCurrentRequest()->getRequestUri();

    // Allow requests to the billing pages.
    if (substr($requested_uri, 0, 8) != '/billing') {
      // Allow requests to the API endpoints.
      if (substr($requested_uri, 0, 5) != '/api/') {
        // Get the user's subscription.
        if ($subscription = $this->beaconBilling->getUserSubscription()) {
          // Check if the subscription is suspended.
          if ($subscription->isSuspended()) {
            // Alert the user.
            drupal_set_message(t('Your subscription is currently suspended. Please reactivate it in order to access this application.'), 'error');

            // Redirect to the manage subscription page.
            $event->setResponse(new RedirectResponse(Url::fromRoute('beacon_billing.manage_subscription')->toString()));
          }
        }
      }
    }
  }

}
