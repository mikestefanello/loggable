<?php

namespace Drupal\beacon_billing\Plugin\Validation\Constraint;

use Drupal\user\UserInterface;
use Drupal\beacon_billing\BeaconBilling;
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
   * Creates a new PathAliasConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param Drupal\beacon_billing\BeaconBilling $beacon_billing
   *   The beacon billing service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, BeaconBilling $beacon_billing) {
    $this->entityTypeManager = $entity_type_manager;
    $this->beaconBilling = $beacon_billing;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('beacon_billing')
    );
  }

}
