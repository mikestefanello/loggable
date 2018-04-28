<?php

namespace Drupal\beacon_ui\Plugin\Menu\LocalAction;

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
