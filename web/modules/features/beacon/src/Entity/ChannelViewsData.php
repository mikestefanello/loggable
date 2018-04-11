<?php

namespace Drupal\beacon\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Channel entities.
 */
class ChannelViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['channel']['channel_bulk_form'] = [
      'title' => $this->t('Channel operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple channels.'),
      'field' => [
        'id' => 'beacon_entity_bulk_form',
      ],
    ];

    return $data;
  }

}
