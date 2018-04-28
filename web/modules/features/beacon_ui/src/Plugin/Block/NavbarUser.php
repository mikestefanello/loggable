<?php

namespace Drupal\beacon_ui\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a block that contains the links for the user section of the navbar..
 *
 * @Block(
 *   id = "navbar_user",
 *   admin_label = @Translation("Navbar: User"),
 *   category = @Translation("Rex"),
 * )
 */
class NavbarUser extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu = [
      'label' => t('Open user menu'),
      'icon' => 'user',
      'links' => [
        [
          'title' => t('Edit account'),
          'url' => Url::fromRoute('beacon_ui.user_edit'),
          'icon' => 'cog',
        ],
        [
          'title' => t('Log out'),
          'url' => Url::fromRoute('user.logout'),
          'icon' => 'sign-out',
        ],
      ],
    ];

    return [
      'menu' => $menu,
    ];
  }

}
