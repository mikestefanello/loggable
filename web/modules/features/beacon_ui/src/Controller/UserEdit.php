<?php

namespace Drupal\beacon_ui\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\user\Entity\User;

/**
 * Class UserEdit.
 *
 * This allows us to expose a route to edit a user account that does not
 * have to be dynamic with the user ID, thus the element that the link resides
 * in can be cached indefinitely. It also allows for editing without the other
 * local tasks and the user ID exposed in the URL.
 */
class UserEdit extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Drupal\Core\Entity\EntityFormBuilder definition.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilder
   */
  protected $entityFormBuilder;

  /**
   * Construct a UserEdit class.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityFormBuilder $entity_form_builder
   *   The entity form builder.
   */
  public function __construct(AccountInterface $account, EntityFormBuilder $entity_form_builder) {
    $this->account = $account;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * Provide the edit form for the current user..
   *
   * @return array
   *   A renderable user entity edit form.
   */
  public function form() {
    return $this->entityFormBuilder->getForm(User::load($this->account->id()), 'default');
  }

}
