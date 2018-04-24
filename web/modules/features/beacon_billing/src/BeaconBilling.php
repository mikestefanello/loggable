<?php

namespace Drupal\beacon_billing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Psr\Log\LoggerInterface;
use Drupal\beacon_billing\Entity\Subscription;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;
use Stripe\Customer as StripeCustomer;
use Stripe\Invoice as StripeInvoice;
use Stripe\Error\Card as StripeExceptionCard;

/**
 * Class to power the Beacon billing system.
 */
class BeaconBilling {

  /**
   * The amount of invoices to fetch.
   *
   * @var int
   */
  const INVOICE_AMOUNT = 12;

  /**
   * The lifttime of cache entries, in seconds.
   *
   * @var int
   */
  const CACHE_LIFETIME = 86400;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Stripe cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The mail sender service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs the BeaconBilling object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user account.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *    A Stripe cache.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail sender service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxy $current_user, ConfigFactoryInterface $config_factory, LoggerInterface $logger, CacheBackendInterface $cache, MailManagerInterface $mail_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
    $this->cache = $cache;
    $this->mailManager = $mail_manager;
  }

  /**
   * Return the logger service.
   *
   * @return \Psr\Log\LoggerInterface
   */
  public function getLogger() {
    return $this->logger;
  }

  /**
   * Load the current user, if needed.
   *
   * This is a helper function for functions that accept an optional user.
   *
   * @param \Drupal\user\UserInterface $user
   *   A user entity. If omitted, the current user will be used.
   * @return \Drupal\user\UserInterface
   *   A user entity.
   */
  public function getUser(UserInterface $user = NULL) {
    if ($user) {
      return $user;
    }
    return User::load($this->currentUser->id());
  }

  /**
   * Create a subscription for a given user.
   *
   * @param \Drupal\user\UserInterface $user
   *   A user entity to create a subscription for. If omitted, the current user
   *   will be used.
   * @param string $plan_id
   *   The Stripe plan ID to subscribe the user to, or NULL to use the default.
   * @return \Drupal\beacon_billing\Entity\Subscription|FALSE
   *   A subscription entity, if created, or FALSE if the operation failed.
   */
  public function createSubscription(UserInterface $user = NULL, string $plan_id = NULL) {
    // Load the user.
    $user = $this->getUser($user);

    // Load the billing settings.
    $settings = $this->getSettings();

    try {
      // Initialize Stripe.
      $this->stripe();

      // Get the plan ID, if needed.
      $plan_id = $plan_id ? $plan_id : $settings->get('default_plan_id');

      // Create a customer in Stripe.
      $stripe_customer = StripeCustomer::create([
        'email' => $user->mail->value,
        'metadata' => [
          'name' => $user->getDisplayName(),
        ],
      ]);

      // Create a subscription for the customer.
      $stripe_subscription = StripeSubscription::create([
        'customer' => $stripe_customer->id,
        'items' => [['plan' => $plan_id]],
        'trial_period_days' => $settings->get('trial_period_days'),
      ]);

      // Create a subscription entity.
      $subscription = Subscription::create([
        'name' => $user->getDisplayName(),
        'email' => $user->mail->value,
        'plan' => $plan_id,
        'customer_id' => $stripe_customer->id,
        'subscription_id' => $stripe_subscription->id,
        'status' => $stripe_subscription->status,
        'user_id' => $user->id(),
        'address' => [
          'country_code' => 'US',
        ]
      ]);
      $subscription->save();

      // Log the creation.
      $this->logger->notice('Created a subscription for user %id.', ['%id' => $user->id()]);

      return $subscription;
    }
    catch (\Exception $e) {
      $this->error('Failed to create a subscription for user %id.', ['%id' => $user->id()], $e);
    }

    return FALSE;
  }

  /**
   * Load the billing settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The settings configuration object.
   */
  public function getSettings() {
    return $this->configFactory->get('beacon_billing.settings');
  }

  /**
   * Get a subscription for a given user.
   *
   * @param \Drupal\user\UserInterface $user
   *   A user entity. Omit to use the current user.
   * @return \Drupal\beacon_billing\Entity\Subscription|NULL
   *   A subscription entity, if found, otherwise NULL.
   */
  public function getUserSubscription(User $user = NULL) {
    return $this->subscriptionQuery('user_id', $this->getUser($user)->id());
  }

  /**
   * Get a subscription for a given subscription ID.
   *
   * @param string $id
   *   A subscription ID (not entity ID).
   * @return \Drupal\beacon_billing\Entity\Subscription|NULL
   *   A subscription entity, if found, otherwise NULL.
   */
  public function getSubscriptionById(string $id) {
    return $this->subscriptionQuery('subscription_id', $id);
  }

  /**
   * Helper function to query for subscription entities with a static cache.
   *
   * @param $field
   *   The field to query on.
   * @param $value
   *   The value to query for.
   * @return \Drupal\beacon_billing\Entity\Subscription|NULL
   *   A subscription entity, if found, otherwise NULL.
   */
  private function subscriptionQuery($field, $value) {
    $ids = &drupal_static(__METHOD__, []);

    // Generate a cache ID.
    $cache_id = "{$field}:{$value}";

    // Check if we have a subscription entity ID already.
    if (!isset($ids[$cache_id])) {
      // Query to find a subscription.
      $results = $this->entityTypeManager
        ->getStorage('subscription')
        ->getQuery()
        ->condition($field, $value)
        ->execute();

      // Extract the results.
      $ids[$cache_id] = !empty($results) ? array_pop($results) : FALSE;
    }

    return $ids[$cache_id] ? Subscription::load($ids[$cache_id]) : NULL;
  }

  /**
   * Sync a subscription with Stripe.
   *
   * This is often used prior to saving the subscription entity in Drupal via
   * the entity form.
   *
   * Expect the subscription entity to change during this sync.
   *
   * @param \Drupal\beacon_billing\Entity\Subscription $subscription
   *   A subscription entity.
   * @param string $cc_token
   *   An optional credit card source token from Stripe.
   * @return mixed
   *   The \Drupal\beacon_billing\Entity\Subscription subscription entity, if successful.
   *   NULL if the operation was skipped.
   *   FALSE if the API call failed.
   *   \Stripe\Error\Card if the payment information was declined.
   */
  public function syncSubscription(Subscription $subscription, $cc_token = NULL) {
    // Stop if there is no customer ID.
    if (!$subscription->getCustomerId()) {
      return NULL;
    }

    try {
      // Initialize Stripe.
      $this->stripe();

      // Fetch the customer.
      $stripe_customer = StripeCustomer::retrieve($subscription->getCustomerId());

      // Update the values.
      $stripe_customer->email = $subscription->email->value;
      $stripe_customer->metadata['name'] = $subscription->name->value;

      // Store the token, if one is present.
      if ($cc_token) {
        $stripe_customer->source = $cc_token;
      }

      // Save the customer.
      $stripe_customer->save();

      // Check if the token was passed in.
      if ($cc_token) {
        // Store the last 4 digits of the card.
        $subscription->set('cc_last_4', $stripe_customer->sources->data[0]->last4);
      }

      // Check if the subscription is not cancelled.
      if ($subscription->getStatus() != StripeSubscription::STATUS_CANCELED) {
        // Ensure there is a subscription ID.
        if ($subscription->getSubscriptionId()) {
          // Fetch the subscription.
          $stripe_subscription = StripeSubscription::retrieve($subscription->getSubscriptionId());

          // Load the tax information for this subscription.
          $tax = $this->getSubscriptionTaxInformation($subscription);

          // Update the tax rate.
          $stripe_subscription->tax_percent = $tax['tax_rate'];

          // Merge in tax metadata.
          $stripe_subscription->metadata->updateAttributes($tax['metadata']);

          // TODO: Update the plan!

          // Save the subscription.
          $stripe_subscription->save();
        }
      }

      return $subscription;
    }
    catch (StripeExceptionCard $e) {
      $this->logger->notice('Declined payment information while updating subscription %id.', ['%id' => $subscription->id()]);
      watchdog_exception('beacon_billing', $e, NULL, [], RfcLogLevel::NOTICE);
      return $e;
    }
    catch (\Exception $e) {
      $this->error('Failed to sync subscription customer data for subscription %id.', ['%id' => $subscription->id()], $e);
      return FALSE;
    }
  }

  /**
   * Fetch invoices for a given subscription.
   *
   * @param \Drupal\beacon_billing\Entity\Subscription $subscription
   *   A subscription entity, or NULL to attempt to find the current user's
   *   company.
   * @param bool $reset
   *   TRUE if the cache should be reset, otherwise FALSE. Defaults to FALSE.
   * @return array|FALSE
   *   An array of Stripe invoice objects, or FALSE if an error occurred.
   */
  public function getInvoices(Subscription $subscription = NULL, $reset = FALSE) {
    // Check if a subscription was not provided.
    if (!$subscription) {
      // Attempt to load the current user's subscription.
      if (!($subscription = $this->getUserSubscription())) {
        return [];
      }
    }

    // Return nothing if this subscription is missing a customer ID.
    if (!$subscription->getCustomerId()) {
      return [];
    }

    // Generate the cache ID.
    $cache_id = 'invoices:' . $subscription->getCustomerId();

    // Check the cache.
    if (!$reset && ($cache = $this->cache->get($cache_id))) {
      return $cache->data;
    }

    try {
      // Initialize Stripe.
      $this->stripe();

      // Build the request parameters.
      $params = [
        'limit' => self::INVOICE_AMOUNT,
        'customer' => $subscription->getCustomerId(),
      ];

      // Fetch a list of invoices.
      $invoices = StripeInvoice::all($params)->data;

      // Cache the data.
      $this->cache->set($cache_id, $invoices, time() + self::CACHE_LIFETIME, ['stripe_customer:' . $subscription->getCustomerId()]);

      return $invoices;
    }
    catch (\Exception $e) {
      $this->error('Failed to fetch invoices for subscription %id.', ['%id' => $subscription->id()], $e);
      return FALSE;
    }
  }

  /**
   * Fetch the upcoming invoice for a given subscription.
   *
   * @param \Drupal\beacon_billing\Entity\Subscription $subscription
   *   A subscription entity, or NULL to attempt to find the current user's
   *   company.
   * @param bool $reset
   *   TRUE if the cache should be reset, otherwise FALSE. Defaults to FALSE.
   * @return \Stripe\Invoice|FALSE|NULL
   *   A Stripe invoice object, FALSE if an error occurred, or NULL if there`is
   *   no upcoming invoice..
   */
  public function getUpcomingInvoice(Subscription $subscription = NULL, $reset = FALSE) {
    // Check if a subscription was not provided.
    if (!$subscription) {
      // Attempt to load the current user's subscription.
      if (!($subscription = $this->getUserSubscription())) {
        return NULL;
      }
    }

    // Return nothing if this subscription is cancelled.
    if ($subscription->getStatus() == StripeSubscription::STATUS_CANCELED) {
      return NULL;
    }

    // Return nothing if this subscription is missing a customer ID.
    if (!$subscription->getCustomerId()) {
      return NULL;
    }

    // Generate the cache ID.
    $cache_id = 'upcoming_invoice:' . $subscription->getCustomerId();

    // Check the cache.
    if (!$reset && ($cache = $this->cache->get($cache_id))) {
      return $cache->data;
    }

    try {
      // Initialize Stripe.
      $this->stripe();

      // Build the request parameters.
      $params = [
        'customer' => $subscription->getCustomerId(),
      ];

      // Fetch the upcoming invoice.
      $invoice = StripeInvoice::upcoming($params);

      // Cache the data.
      $this->cache->set($cache_id, $invoice, time() + self::CACHE_LIFETIME, ['stripe_customer:' . $subscription->getCustomerId()]);

      return $invoice;
    }
    catch (\Exception $e) {
      $this->error('Failed to fetch upcoming invoice for subscription %id.', ['%id' => $subscription->id()], $e);
      return FALSE;
    }
  }

  /**
   * Clear the Stripe data cache for a given subscription.
   *
   * @param \Drupal\beacon_billing\Entity\Subscription $subscription
   *   A subscription entity, or NULL to attempt to find the current user's
   *   company.
   */
  public function clearCache(Subscription $subscription = NULL) {
    // Check if a subscription was not provided.
    if (!$subscription) {
      // Attempt to load the current user's subscription.
      if (!($subscription = $this->getUserSubscription())) {
        return;
      }
    }

    // Clear the cache.
    Cache::invalidateTags([
      'stripe_customer:' . $subscription->getCustomerId(),
      'stripe_subscription:' . $subscription->getSubscriptionId(),
    ] + $subscription->getCacheTags());
  }

  /**
   * Cancel a given subscription.
   *
   * @param \Drupal\beacon_billing\Entity\Subscription $subscription
   *   A subscription entity, or NULL to attempt to find the current user's
   *   company.
   * @param bool $delete
   *   TRUE if the subscription entity is being deleted, otherwise FALSE. Defaults
   *   to FALSE.
   * @return boolean
   *   TRUE if the operation was successful, otherwise FALSE.
   */
  public function cancelSubscription(Subscription $subscription = NULL, $delete = FALSE) {
    // Check if a subscription was not provided.
    if (!$subscription) {
      // Attempt to load the current user's subscription.
      if (!($subscription = $this->getUserSubscription())) {
        return FALSE;
      }
    }

    // Stop if this subscription is missing a subscription ID.
    if (!$subscription->getSubscriptionId()) {
      return FALSE;
    }

    // Stop if this subscription is cancelled already.
    if ($subscription->getStatus() == StripeSubscription::STATUS_CANCELED) {
      return FALSE;
    }

    try {
      // Initialize Stripe.
      $this->stripe();

      // Fetch the subscription.
      $stripe_subscription = StripeSubscription::retrieve($subscription->getSubscriptionId());

      // Cancel the subscription.
      $stripe_subscription->cancel();

      // Update the status of the subscription in Drupal.
      if (!$delete) {
        $subscription->set('status', StripeSubscription::STATUS_CANCELED)->save();
      }

      // Clear the cache.
      $this->clearCache($subscription);

      // Log the action.
      $this->logger->notice('Subscription %id was cancelled.', ['%id' => $subscription->id()]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->error('Failed to cancel subscription %id.', ['%id' => $subscription->id()], $e);
      return FALSE;
    }
  }

  /**
   * Reactivate a given subscription.
   *
   * @param \Drupal\beacon_billing\Entity\Subscription $subscription
   *   A subscription entity, or NULL to attempt to find the current user's
   *   company.
   * @return mixed
   *   TRUE if the operation was successful.
   *   NULL if the operation was not attempted.
   *   FALSE if the API call failed.
   *   \Stripe\Error\Card if the payment failed.
   */
  public function reactivateSubscription(Subscription $subscription = NULL) {
    // Check if a subscription was not provided.
    if (!$subscription) {
      // Attempt to load the current user's subscription.
      if (!($subscription = $this->getUserSubscription())) {
        return NULL;
      }
    }

    // Stop if this subscription is missing a customer ID.
    if (!$subscription->getCustomerId()) {
      return NULL;
    }

    // Load the billing settings.
    $settings = $this->getSettings();

    try {
      // Initialize Stripe.
      $this->stripe();

      // Check if the subscription was canceled.
      if ($subscription->getStatus() == StripeSubscription::STATUS_CANCELED) {
        // Load the tax rate information.
        $tax = $this->getSubscriptionTaxInformation($subscription);

        // Create a new subscription.
        $stripe_subscription = StripeSubscription::create([
          'customer' => $subscription->getCustomerId(),
          'items' => [
            [
              'plan' => $subscription->plan->value,
              'quantity' => $this->getSubscriptionQuantity($subscription),
            ],
          ],
          'tax_percent' => $tax['tax_rate'],
        ]);

        // Add tax metadata..
        $stripe_subscription->metadata->updateAttributes($tax['metadata']);

        // Update the subscription ID in Drupal.
        $subscription->set('subscription_id', $stripe_subscription->id);
      }
      // Check if the subscription was unpaid.
      elseif ($subscription->getStatus() == StripeSubscription::STATUS_UNPAID) {
        // Load the invoices.
        $invoices = $this->getInvoices($subscription, TRUE);

        // Track if we found an unpaid invoice.
        $invoice_paid = FALSE;

        // Iterate to find the latest unpaid invoice.
        foreach ($invoices as $invoice) {
          if (!$invoice->paid) {
            // Attempt to pay this invoice.
            $invoice->pay();
            $invoice_paid = TRUE;
            break;
          }
        }

        // Check if we never found an invoice to pay.
        if (!$invoice_paid) {
          throw new \Exception('Cannot reactivate unpaid subscription with no unpaid invoices.');
        }
      }
      else {
        throw new \Exception('Only canceled and unpaid subscriptions can be reactivated.');
      }

      // Update the subscription status in Drupal.
      $subscription
        ->set('status', StripeSubscription::STATUS_ACTIVE)
        ->save();

      // Clear the cache.
      $this->clearCache($subscription);

      // Log the action.
      $this->logger->notice('Subscription %id was reactivated.', ['%id' => $subscription->id()]);

      return TRUE;
    }
    catch (StripeExceptionCard $e) {
      $this->logger->notice('Payment failure while reactivating subscription %id.', ['%id' => $subscription->id()]);
      watchdog_exception('beacon_billing', $e, NULL, [], RfcLogLevel::NOTICE);
      return $e;
    }
    catch (\Exception $e) {
      $this->error('Failed to reactivate subscription %id.', ['%id' => $subscription->id()], $e);
      return FALSE;
    }
  }

  /**
   * Update the quantity of a given subscription.
   *
   * @param \Drupal\beacon_billing\Entity\Subscription $subscription
   *   A subscription entity, or NULL to attempt to find the current user's
   *   company.
   * @return mixed
   *   A \Stripe\Subscription if the operation was successful.
   *   NULL if the operation was skipped.
   *   FALSE if the operation failed.
   */
  public function updateSubscriptionQuantity(Subscription $subscription = NULL) {
    // Check if a subscription was not provided.
    if (!$subscription) {
      // Attempt to load the current user's subscription.
      if (!($subscription = $this->getUserSubscription())) {
        return NULL;
      }
    }

    // Stop if this subscription is missing a subscription ID.
    if (!$subscription->getSubscriptionId()) {
      return NULL;
    }

    // Load the billing settings.
    $settings = $this->getSettings();

    try {
      // Initialize Stripe.
      $this->stripe();

      // Fetch the subscription.
      $stripe_subscription = StripeSubscription::retrieve($subscription->getSubscriptionId());

      // Update the quantity.
      $stripe_subscription->quantity = $this->getSubscriptionQuantity($subscription);

      // Save the subscription.
      $stripe_subscription->save();

      // Clear the cache.
      $this->clearCache($subscription);

      // Log the action.
      $this->logger->notice('Quantity updated for subscription %id.', ['%id' => $subscription->id()]);

      return $stripe_subscription;
    }
    catch (\Exception $e) {
      $this->error('Failed to update quantity for subscription %id.', ['%id' => $subscription->id()], $e);
      return FALSE;
    }
  }

  /**
   * Reactivate a given subscription.
   *
   * @param \Drupal\beacon_billing\Entity\Subscription $subscription
   *   A subscription entity.
   * @return array
   *   An array of tax information for a given subscription. Includes:
   *     'metadata': An array of metadata that explains the taxes.
   *     'tax_rate': The effective tax rate (0-100).
   */
  public function getSubscriptionTaxInformation(Subscription $subscription) {
    // Default the tax info to return.
    $info = [
      'metadata' => [
        'TaxRegionName' => 'None',
        'TaxStateRate' => 0,
        'TaxEstimatedCombinedRate' => 0,
        'TaxEstimatedCountyRate' => 0,
        'TaxEstimatedCityRate' => 0,
      ],
      'tax_rate' => 0,
    ];

    // Extract the address.
    $address = $subscription->address->first()->getValue();

    // Extract the state.
    $state = $address['administrative_area'];

    // Check if the address is a state in the US.
    if ($state && ($address['country_code'] == 'US')) {
      // Load the rate table.
      $rates = $this->loadTaxRates();

      // Check if there is a tax rate for the zip code.
      if (isset($rates[$address['postal_code']])) {
        // Extract the rate info.
        $rate = $rates[$address['postal_code']];

        // Add the tax rate.
        $info['tax_rate'] = number_format($rate['EstimatedCombinedRate'] * 100, 4);

        // Add metadata.
        $info['metadata'] = [
          'TaxRegionName' => $rate['TaxRegionName'],
          'TaxStateRate' => $rate['StateRate'],
          'TaxEstimatedCombinedRate' => $rate['EstimatedCombinedRate'],
          'TaxEstimatedCountyRate' => $rate['EstimatedCountyRate'],
          'TaxEstimatedCityRate' => $rate['EstimatedCityRate'],
        ];
      }
    }

    return $info;
  }

  /**
   * Load the tax rates from the CSV data file.
   *
   * @return array
   *   An array of tax rates keyed by zip code.
   */
  public static function loadTaxRates() {
    $rows = &drupal_static(__METHOD__, NULL);

    // Return if the cache was populated.
    if ($rows !== NULL) {
      return $rows;
    }

    // Open the tax rates file.
    $file = fopen(drupal_get_path('module', 'beacon_billing') . '/data/tax_rates.csv', 'r');
    $rows = $headers = [];

    // Iterate the file rows.
    while ($data = fgetcsv($file, 0, ',')) {
      // Check if the headers were not yet populated.
      if (empty($headers)) {
        $headers = $data;
        continue;
      }

      // Create the row.
      $row = [];
      foreach ($data as $index => $value) {
        // Key using the headers.
        $row[$headers[$index]] = $value;
      }

      // Add the row using the zip as the key.
      $rows[$row['ZipCode']] = $row;
    }

    // Close the file.
    fclose($file);

    return $rows;
  }

  /**
   * Calculate the quantity for a given subscription.
   *
   * This counts the amount of channels the user has.
   *
   * If the user has no channels, a value of 1 will be returned as that is the
   * minimum.
   *
   * @param \Drupal\beacon_billing\Entity\Subscription $subscription
   *   A subscription entity.
   * @return int
   *   A subscription quantity.
   */
  public function getSubscriptionQuantity(Subscription $subscription) {
    $quantity = $this->entityTypeManager
      ->getStorage('channel')
      ->getQuery()
      ->condition('user_id', $subscription->getOwnerId())
      ->count()
      ->execute();

    return max($quantity, 1);
  }

  /**
   * Initialize Stripe, if possible.
   *
   * @throws \Exception
   */
  public function stripe() {
    // Load the Stripe settings.
    $settings = $this->configFactory->get('stripe.settings')->get();

    // Check if the environment is set.
    if (!empty($settings['environment'])) {
      // Extract the API key.
      $api_key = $settings['apikey'][$settings['environment']]['secret'];

      // Check if an API key exists for this environment.
      if ($api_key) {
        // Initialize Stripe.
        Stripe::setApiKey($api_key);

        return;
      }
    }

    throw new \Exception('Stripe is not configured.');
  }

  /**
   * Log an error and email the site email address.
   *
   * @param string $message
   *   The error message text.
   * @param array $params
   *   An optional array of placeholders in the message.
   * @param \Exception $e
   *   An optional exception that is associated with the error.
   */
  public function error($message, array $params = [], \Exception $e = NULL) {
    // Log the error.
    $this->logger->error($message, $params);

    // Log the exception.
    if ($e) {
      watchdog_exception('beacon_billing', $e);
    }

    // Get the settings.
    $settings = $this->getSettings();

    // Check if there is an alert email address.
    if ($email = $settings->get('alert_email')) {
      // Build the params for the email.
      $params = [
        'message' => strtr($message, $params),
        'exception' => $e,
      ];

      // Send the email.
      $this->mailManager->mail('beacon_billing', 'billing_error', $email, 'en', $params);
    }
  }
}
