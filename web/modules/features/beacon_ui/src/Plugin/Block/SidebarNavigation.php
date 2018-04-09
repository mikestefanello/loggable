<?php

namespace Drupal\beacon_ui\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Url;

/**
 * Provides a block that contains the sidebar navigation.
 *
 * @Block(
 *   id = "sidebar_navigation",
 *   admin_label = @Translation("Sidebar navigation"),
 *   category = @Translation("Rex"),
 * )
 */
class SidebarNavigation extends BlockBase implements ContainerFactoryPluginInterface {

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
    $items = [];

    // Load the current user.
    $user = User::load($this->account->id());

    // Add support link.
    $items['support'] = [
      'title' => t('Support'),
      'url' => Url::fromRoute('beacon_ui.support'),
      'below' => [],
      'icon' => 'question-circle',
    ];

    // Check if this user is a site admin.
    if ($user->hasPermission('administer site configuration')) {
      // Add Drupal admin links.
      $items['drupal'] = [
        'title' => t('Drupal'),
        'url' => Url::fromRoute('<none>', [], ['fragment' => 'drupal']),
        'below' => [
          'content' => [
            'title' => t('Content'),
            'url' => Url::fromRoute('system.admin_content'),
          ],
          'structure' => [
            'title' => t('Structure'),
            'url' => Url::fromRoute('system.admin_structure'),
          ],
          'appearance' => [
            'title' => t('Appearance'),
            'url' => Url::fromRoute('system.themes_page'),
          ],
          'extend' => [
            'title' => t('Extend'),
            'url' => Url::fromRoute('system.modules_list'),
          ],
          'people' => [
            'title' => t('People'),
            'url' => Url::fromRoute('entity.user.collection'),
          ],
          'config' => [
            'title' => t('Configuration'),
            'url' => Url::fromRoute('system.admin_config'),
          ],
          'reports' => [
            'title' => t('Reports'),
            'url' => Url::fromRoute('system.admin_reports'),
          ],
        ],
        'icon' => 'drupal',
      ];
    }

    return [
      'items' => $items,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeTags(parent::getCacheContexts(), [
      'user.roles',
      'user.permissions'
    ]);
  }

}
