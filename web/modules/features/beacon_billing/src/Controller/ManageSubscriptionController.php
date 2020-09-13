<?php

namespace Drupal\beacon_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\beacon_billing\BeaconBilling;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ManageSubscriptionController.
 */
class ManageSubscriptionController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityFormBuilder definition.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilder
   */
  protected $entityFormBuilder;

  /**
   * Drupal\beacon_billing\BeaconBilling definition.
   *
   * @var \Drupal\beacon_billing\BeaconBilling
   */
  protected $beaconBilling;

  /**
   * Constructs a new ManageSubscriptionController object.
   */
  public function __construct(EntityFormBuilder $entity_form_builder, BeaconBilling $beacon_billing) {
    $this->entityFormBuilder = $entity_form_builder;
    $this->beaconBilling = $beacon_billing;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.form_builder'),
      $container->get('beacon_billing')
    );
  }

  /**
   * Provide the subscription entity edit form for the user's subscription.
   */
  public function form() {
    // Get the user's subscription.
    if ($subscription = $this->beaconBilling->getUserSubscription()) {
      return $this->entityFormBuilder->getForm($subscription, 'default');
    }

    // Redirect back home.
    drupal_set_message(t('No subscription found.'), 'error');
    return new RedirectResponse('/');
  }

}
