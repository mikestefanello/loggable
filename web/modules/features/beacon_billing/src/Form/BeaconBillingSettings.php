<?php

namespace Drupal\beacon_billing\Form;

use Drupal\beacon_billing\Plugin\SubscriptionPlanManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BeaconBillingSettings.
 */
class BeaconBillingSettings extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The subscription plan plugin manager.
   *
   * @var \Drupal\beacon_billing\Plugin\SubscriptionPlanManager
   */
  protected $subscriptionPlanManager;

  /**
   * Constructs a new BeaconBillingSettings.
   *
   * @param \Drupal\beacon_billing\Plugin\SubscriptionPlanManager $subscription_plan_manager
   *   The subscription plan plugin manager.
   */
  public function __construct(SubscriptionPlanManager $subscription_plan_manager) {
    $this->subscriptionPlanManager = $subscription_plan_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.subscription_plan')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'beacon_billing.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'beacon_billing_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('beacon_billing.settings');
    $form['default_plan_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Default plan ID'),
      '#description' => $this->t('Choose the default subscription plan ID to subscribe users to initially.'),
      '#default_value' => $config->get('default_plan_id'),
      '#options' => [],
      '#required' => TRUE,
    ];
    $form['trial_period_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Free trial period (in days)'),
      '#description' => $this->t('Enter the amount of days the free trial period is for.'),
      '#default_value' => $config->get('trial_period_days'),
      '#required' => TRUE,
      '#min' => 0,
    ];
    $form['alert_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Alert email address'),
      '#description' => $this->t('Enter an email address to be alerted whenever there are billing or subscription errors.'),
      '#default_value' => $config->get('alert_email'),
    ];

    // Add the subscription plans as options.
    foreach ($this->subscriptionPlanManager->getDefinitions() as $plan_id => $plan) {
      $form['default_plan_id']['#options'][$plan_id] = $plan['label'];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Save the config.
    $this->config('beacon_billing.settings')
      ->set('default_plan_id', $form_state->getValue('default_plan_id'))
      ->set('trial_period_days', $form_state->getValue('trial_period_days'))
      ->set('alert_email', $form_state->getValue('alert_email'))
      ->save();
  }

}
