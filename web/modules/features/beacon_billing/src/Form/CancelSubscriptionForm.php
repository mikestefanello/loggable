<?php

namespace Drupal\beacon_billing\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\beacon\Beacon;
use Drupal\beacon_billing\BeaconBilling;
use Drupal\Core\Access\AccessResult;
use Stripe\Subscription as StripeSubscription;
use Drupal\Core\Url;

/**
 * Class CancelSubscriptionForm.
 */
class CancelSubscriptionForm extends FormBase {

  /**
   * Drupal\beacon\Beacon definition.
   *
   * @var \Drupal\beacon\Beacon
   */
  protected $beacon;

  /**
   * Drupal\beacon_billing\BeaconBilling definition.
   *
   * @var \Drupal\beacon_billing\BeaconBilling
   */
  protected $beaconBilling;

  /**
   * Constructs a new CancelSubscriptionForm object.
   */
  public function __construct(Beacon $beacon, BeaconBilling $beacon_billing) {
    $this->beacon = $beacon;
    $this->beaconBilling = $beacon_billing;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('beacon'),
      $container->get('beacon_billing')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cancel_subscription_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['header'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Are you sure you want to cancel your subscription?'),
    ];
    $form['warning'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Cancelling your subscription will immediately restrict access to this service for you and all of your company members. Your company data may be permanently deleted six months after your subscription is cancelled.'),
    ];
    $form['billing'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Refunds are not available for any remaining time on your current subscription. If you wish to reactivate your subscription after cancelling, you will be billed at that time.'),
    ];
    $form['confirm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I confirm the cancellation of this subscription'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel subscription'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Cancel the subscription.
    $success = $this->beaconBilling->cancelSubscription();

    // Check if the operation was a success.
    if ($success) {
      // Alert the user.
      drupal_set_message($this->t('Your subscription has been cancelled.'));

      // Redirect to the billing page.
      $form_state->setRedirect('beacon_billing.manage_subscription');
    }
    else {
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
      $this->beacon->userHasCompanyAdminRole() &&
      $subscription &&
      $subscription->getSubscriptionId() &&
      ($subscription->getStatus() != StripeSubscription::STATUS_CANCELED)
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
