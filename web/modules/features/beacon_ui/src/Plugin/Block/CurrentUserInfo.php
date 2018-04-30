<?php

namespace Drupal\beacon_ui\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;

/**
 * Provides a simple block with user info of the current user.
 *
 * @Block(
 *   id = "current_user_info",
 *   admin_label = @Translation("Current user info"),
 *   category = @Translation("Rex"),
 * )
 */
class CurrentUserInfo extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Load the current user.
    $user = User::load($this->account->id());

    // Extract the user's email.
    $mail = $user->mail->value;

    // Check if it needs to be trimmed.
    if (strlen($mail) > 23) {
      $mail = substr($mail, 0, 23) . '...';
    }

    // Return the user info.
    return [
      'name' => [
        '#markup' => $user->getDisplayName(),
      ],
      'designation' => [
        '#markup' => $mail,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeTags(parent::getCacheContexts(), ['user']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['user:' . $this->account->id()]);
  }

}
