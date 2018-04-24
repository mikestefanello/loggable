<?php

namespace Drupal\beacon_billing\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\beacon_billing\BeaconBilling;
use Drupal\Core\Access\AccessResult;
use Stripe\Subscription as StripeSubscription;
use Drupal\Core\Url;

/**
 * Class ReactivateSubscriptionForm.
 */
class ReactivateSubscriptionForm extends FormBase {

  /**
   * Drupal\beacon_billing\BeaconBilling definition.
   *
   * @var \Drupal\beacon_billing\BeaconBilling
   */
  protected $beaconBilling;

  /**
   * Constructs a new ReactivateSubscriptionForm object.
   */
  public function __construct(BeaconBilling $beacon_billing) {
    $this->beaconBilling = $beacon_billing;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('beacon_billing')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reactivate_subscription_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load the user's subscription.
    $subscription = $this->beaconBilling->getUserSubscription();

    // Check if the subscription is missing a card.
    if (!$subscription->hasCard()) {
      // Return a message.
      $form['no_card'] = [
        '#type' => 'item',
        '#markup' => $this->t('Please add a credit card and confirm your billing information before reactivating your subscription.')
      ];
      return $form;
    }

    $form['header'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Are you sure you want to reactivate your subscription?'),
    ];
    $form['warning'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Reactivating your subscription will immediately restore access to this service for you.'),
    ];
    $form['billing'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('You will be billed upon submitting this form based on the number of channels your account has.'),
    ];
    $form['billing_info'] = [
      '#type' => 'html_tag',
      '#tag' => 'strong',
      '#value' => $this->t('Please make sure your billing information is accurate and up-to-date prior to reactivating your subscription.'),
    ];
    $form['confirm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I confirm the reactivation of this subscription and acknowledge that my credit card will be billed'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reactivate subscription'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Reactivate the subscription.
    $response = $this->beaconBilling->reactivateSubscription();

    // Check for a payment error.
    if (is_object($response) && (get_class($response) == 'Stripe\Error\Card')) {
      // Alert the user.
      drupal_set_message($response->getMessage(), 'error');
      drupal_set_message(t('Please update your billing information and try again.'), 'error');

      // Redirect to the billing page.
      $form_state->setRedirect('beacon_billing.manage_subscription');
      return;
    }

    // Check if the operation was a success.
    if ($response === TRUE) {
      // Alert the user.
      drupal_set_message($this->t('Your subscription has been reactivated.'));

      // Redirect to the billing page.
      $form_state->setRedirect('beacon_billing.manage_subscription');
    }

    // Check for a failed response.
    if ($response === FALSE) {
      // Alert the user.
      drupal_set_message($this->t('An error occurred. Please try again or contact support for assistance.'), 'error');
    }
  }

  /**
   * Access hander for form.
   */
  public function access() {
    // Get the user's subscription.
    $subscription = $this->beaconBilling->getUserSubscription();

    // Determine access.
    $access = AccessResult::allowedIf(
      $subscription &&
      in_array($subscription->getStatus(), [StripeSubscription::STATUS_CANCELED, StripeSubscription::STATUS_UNPAID])
    );

    // Cache per user.
    $access->cachePerUser();

    // Add the subscription as a cache dependency.
    if ($subscription) {
      $access->addCacheableDependency($subscription);
    }

    return $access;
  }

}
