<?php

namespace Drupal\beacon_ui\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\user\Entity\User;

/**
 * Class UserEditRedirect.
 *
 * This allows us to expose a route to edit a user account that does not
 * have to be dynamic with the user ID, thus the element that the link resides
 * in can be cached indefinitely.
 */
class UserEditRedirect extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Construct a UserEditRedirect class.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(AccountInterface $account) {
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Redirect the user to their user edit form.
   *
   * @return Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function redirectUser() {
    return new RedirectResponse(User::load($this->account->id())->url('edit-form'));
  }

}
