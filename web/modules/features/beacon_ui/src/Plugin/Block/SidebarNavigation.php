<?php

namespace Drupal\beacon_ui\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructs a SidebarNavigation object.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $account, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $items = [];

    // Add a dashboard link.
    $items['dashboard'] = [
      'title' => t('Dashboard'),
      'url' => Url::fromRoute('<none>', [], ['fragment' => 'dashboard']),
      'icon' => 'desktop',
      'below' => [],
    ];

    // Load channel storage.
    $channel_storage = $this->entityTypeManager
      ->getStorage('channel');

    // Find channels that the current user owns.
    $channels = $channel_storage
      ->getQuery()
      ->condition('user_id', $this->account->id())
      ->sort('name')
      ->execute();

    // Load the channels.
    $channels = $channels ? $channel_storage->loadMultiple($channels) : [];

    // Add channel links.
    $items['channels'] = [
      'title' => t('Channels'),
      'url' => Url::fromRoute('<none>', [], ['fragment' => 'channels']),
      'icon' => 'filter',
      'below' => [],
    ];

    // Add a link for each channel.
    foreach ($channels as $channel) {
      $items['channels']['below'][] = [
        'title' => $channel->label(),
        'url' => $channel->url(),
      ];
    }

    // Check if the user can create channels.
    if ($this->entityTypeManager->getAccessControlHandler('channel')->createAccess()) {
      // Add a link to add a channel.
      $items['channels']['below']['add'] = [
        'title' => t('Add channel'),
        'url' => Url::fromRoute('entity.channel.add_form'),
        'icon' => 'plus',
      ];
    }

    // Add events link.
    $items['events'] = [
      'title' => t('Events'),
      'url' => Url::fromRoute('view.beacon_events.page_1'),
      'icon' => 'database',
      'below' => [],
    ];

    // Add alerts link.
    $items['alerts'] = [
      'title' => t('Alerts'),
      'url' => Url::fromRoute('view.beacon_alerts.page_1'),
      'icon' => 'bullhorn',
      'below' => [],
    ];

    // Add API link.
    $items['api'] = [
      'title' => t('API information'),
      'url' => Url::fromRoute('<none>', [], ['fragment' => 'api']),
      'icon' => 'plug',
      'below' => [],
    ];

    // Add support link.
    $items['support'] = [
      'title' => t('Support'),
      'url' => Url::fromRoute('beacon_ui.support'),
      'below' => [],
      'icon' => 'question-circle',
    ];

    // Check if this user is a site admin.
    if ($this->account->hasPermission('administer site configuration')) {
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

    return ['items' => $items];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      'user'
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), [
      'user.channels:' . $this->account->id(),
    ]);
  }

}
