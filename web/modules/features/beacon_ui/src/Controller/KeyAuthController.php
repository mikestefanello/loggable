<?php

namespace Drupal\beacon_ui\Controller;

use Drupal\user\Entity\User;
use Drupal\key_auth\Form\UserKeyAuthForm;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Class KeyAuthController.
 *
 * Provides a wrapper around the key auth form.
 */
class KeyAuthController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new KeyAuthController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(FormBuilderInterface $form_builder, AccountProxyInterface $current_user) {
    $this->formBuilder = $form_builder;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('current_user')
    );
  }

  /**
   * Render the key auth form for the current user.
   *
   * @return array
   *   A renderable form.
   */
  public function keyAuthForm() {
    $form = $this->formBuilder->getForm(UserKeyAuthForm::class, User::load($this->currentUser()->id()));
    return $form;
  }

}
