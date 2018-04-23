<?php

namespace Drupal\beacon_billing\Form;

use Drupal\beacon_billing\Plugin\SubscriptionPlanManager;
use Drupal\beacon_billing\BeaconBilling;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Stripe\Subscription as StripeSubscription;
use Drupal\Component\Utility\SortArray;

/**
 * Form controller for Subscription edit forms.
 *
 * @ingroup beacon_billing
 */
class SubscriptionForm extends ContentEntityForm {

  /**
   * The beacon billing service.
   *
   * @var \Drupal\beacon_billing\BeaconBilling
   */
  protected $beaconBilling;

  /**
   * The subscription plan plugin manager.
   *
   * @var \Drupal\beacon_billing\Plugin\SubscriptionPlanManager
   */
  protected $subscriptionPlanManager;

  /**
   * Constructs a SubscriptionForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\beacon_billing\BeaconBilling $beacon_billing
   *   The beacon billing service.
   * @param \Drupal\beacon_billing\Plugin\SubscriptionPlanManager $subscription_plan_manager
   *   The subscription plan plugin manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, BeaconBilling $beacon_billing, SubscriptionPlanManager $subscription_plan_manager) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->beaconBilling = $beacon_billing;
    $this->subscriptionPlanManager = $subscription_plan_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('beacon_billing'),
      $container->get('plugin.manager.subscription_plan')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    // Make sure this is not an AJAX request.
    if (!$form_state->getValue('update_cc_button')) {
      // Check if a credit card is missing.
      if (!$entity->hasCard()) {
        // Check if the subscription is trailing.
        if ($entity->getStatus() == StripeSubscription::STATUS_TRIALING) {
          // Alert the user.
          // TODO: This is showing even after the form submits with a CC.
          drupal_set_message($this->t('Your subscription is currently in trial. Please add a credit card to avoid any service interruptions.'), 'warning');
        }
      }
    }

    // Get the status values.
    $status_values = $entity->getFieldDefinition('status')->getFieldStorageDefinition()->getSetting('allowed_values');

    // Add the subscription status for the user to see.
    $form['subscription_status'] = [
      '#type' => 'details',
      '#title' => $this->t('Subscription status'),
      '#open' => TRUE,
      '#weight' => -50,
      '#access' => (bool) $entity->status->value,
      'markup' => [
        '#markup' => $entity->status->value ? $status_values[$entity->status->value] : '',
      ],
    ];

    // Add the plan to a wrapper.
    $form['plan_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Subscription'),
      '#open' => TRUE,
      '#weight' => -35,
    ];
    $form['plan_wrapper']['plan'] = $form['plan'];
    unset($form['plan']);

    // Convert the plan to a select list.
    $plan_form = &$form['plan_wrapper']['plan']['widget'][0]['value'];
    $plan_form['#type'] = 'select';
    $plan_form['#size'] = NULL;
    $plan_form['#options'] = [];

    // Load the plans.
    $plans = $this->subscriptionPlanManager->getDefinitions();

    // Sort by price.
    usort($plans, function ($a, $b) {
      return SortArray::sortByKeyInt($a, $b, 'price');
    });

    // Add the plan options.
    foreach ($plans as $plan) {
      $plan_form['#options'][$plan['id']] = $plan['label'] . ' ($' . $plan['price'] . '/' . $plan['period']  . ' ' . $this->t('per channel') . ')';

      // Add information about the plan.
      $form['plan_wrapper']['info'][$plan['id']] = [
        '#type' => 'container',
        'label' => [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#value' => $this->t('%plan plan includes', ['%plan' => $plan['label']]),
        ],
        'details' => [
          '#theme' => 'item_list',
          '#items' => [
            $this->t('%quota events per channel', ['%quota' => $plan['quotaEvents']]),
            $this->t('%quota alerts per channel', ['%quota' => $plan['quotaAlerts']]),
            $this->t('%history day event history', ['%history' => $plan['eventHistory']]),
          ],
        ],
        '#states' => [
          'visible' => [
            ':input#edit-plan-0-value' => ['value' => $plan['id']],
          ],
        ],
      ];
    }

    // Wrap the contact information.
    $form['contact'] = [
      '#type' => 'details',
      '#title' => $this->t('Contact information'),
      '#open' => TRUE,
      '#weight' => -25,
    ];

    // Move contact information.
    foreach (['name', 'email'] as $key) {
      $form['contact'][$key] = $form[$key];
      unset($form[$key]);
    }

    // Adjust the address weight.
    $form['address']['#weight'] = $form['address']['widget'][0]['value']['#weight'] = -24;

    // Add a credit cards details element.
    $form['cc_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Credit card'),
      '#open' => TRUE,
      '#weight' => 0,
      'cc_container' => [
        '#type' => 'container',
        '#attributes' => ['id' => 'cc-container'],
      ],
    ];

    // Check if there is either not a stored credit card or the update button
    // was pressed.
    if (!$entity->hasCard() || $form_state->getValue('update_cc_button')) {
      // Add the credit card element.
      $form['cc_wrapper']['cc_container']['cc'] = [
        '#type' => 'stripe',
        "#stripe_selectors" => [
          'name' => ':input[id="edit-name-0-value"]',
          'address_line1' => ':input[id="edit-address-0-address-address-line1"]',
          'address_line2' => ':input[id="edit-address-0-address-address-line2"]',
          'address_city' => ':input[id="edit-address-0-address-locality"]',
          'address_state' => ':input[id="edit-address-0-address-administrative-area"]',
          'address_zip' => ':input[id="edit-address-0-address-postal-code"]',
          'address_country' => ':input[id="edit-address-0-address-country-code--2"]',
        ],
        '#weight' => 50,
      ];
    }
    else {
      // Show the stored card and allow updating.
      $form['cc_wrapper']['cc_container']['update_cc']['last_4'] = [
        '#type' => 'item',
        '#markup' => $this->t('Card ending in %last_4', ['%last_4' => $entity->cc_last_4->value]),
      ];
      $form['cc_wrapper']['cc_container']['update_cc']['update_cc_button'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Update credit card'),
        '#ajax' => [
          'callback' => '::addCreditCardElementAjax',
          'wrapper' => 'cc-container',
          'effect' => 'fade',
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Check if the zip contains invalid characters.
    if (preg_match('/[^\d-]/', $form_state->getValue('address_zip')[0]['value'])) {
      $form_state->setErrorByName('address_zip', t('The zip code can only contain numbers and dashes.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Sync the subscription customer with Stripe.
    $response = $this->beaconBilling->syncSubscription($entity, $form_state->getValue('cc'));

    // Check for a payment error.
    if (is_object($response) && (get_class($response) == 'Stripe\Error\Card')) {
      drupal_set_message($response->getMessage(), 'error');
      return;
    }

    // Check for an API error.
    if ($response === FALSE) {
      drupal_set_message($this->t('An error occurred processing your update. Please try again or contact support for assistance.'), 'error');
      return;
    }

    // Save the entity.
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the subscription successfully.'));
        break;

      default:
        drupal_set_message($this->t('The billing information was updated.'));
    }
  }

  /**
   * AJAX callback to add the credit card element.
   */
  public function addCreditCardElementAjax($form, FormStateInterface $form_state) {
    return $form['cc_wrapper']['cc_container'];
  }

}
