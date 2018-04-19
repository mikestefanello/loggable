<?php

namespace Drupal\beacon_ui\Plugin\Menu\LocalAction;

use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a channel contextual link.
 */
class ChannelEntityContextual extends EntityContextualUuidLocalAction {

  /**
   * {@inheritdoc}
   */
   public function getEntityType() {
     return 'channel';
   }

}
