<?php

namespace Drupal\beacon_billing\Plugin\Validation\Constraint;

use Drupal\user\UserInterface;
use Drupal\beacon_billing\BeaconBilling;
use Drupal\beacon_billing\Plugin\SubscriptionPlanManager;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Base constraint validator for enforcing an entity quota.
 */
abstract class QuotaConstraintValidatorBase extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * Creates a new PathAliasConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param Drupal\beacon_billing\BeaconBilling $beacon_billing
   *   The beacon billing service.
   * @param \Drupal\beacon_billing\Plugin\SubscriptionPlanManager $subscription_plan_manager
   *   The subscription plan plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, BeaconBilling $beacon_billing, SubscriptionPlanManager $subscription_plan_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->beaconBilling = $beacon_billing;
    $this->subscriptionPlanManager = $subscription_plan_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('beacon_billing'),
      $container->get('plugin.manager.subscription_plan')
    );
  }

  /**
   * Get the subscription plan plugin definition for the subscription belonging
   * to a given user.
   *
   * @param  \Drupal\user\UserInterface $user
   *   The user to fetch the subscription from.
   * @return array|NULL
   *   The subscription plan plugin definition, or NULL if no subscription exists.
   */
  public function getSubscriptionPlanDefinition(UserInterface $user) {
    // Load the subscription.
    if ($subscription = $this->beaconBilling->getUserSubscription($user)) {
      // Load the plan ID.
      if ($plan_id = $subscription->plan->value) {
        // Load the plan plugin definition.
        return $this->subscriptionPlanManager
          ->getDefinition($plan_id);
      }
    }

    return NULL;
  }

}
