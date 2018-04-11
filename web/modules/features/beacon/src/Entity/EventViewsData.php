<?php

namespace Drupal\beacon\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Event entities.
 */
class EventViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['event']['event_bulk_form'] = [
      'title' => $this->t('Event operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple events.'),
      'field' => [
        'id' => 'beacon_entity_bulk_form',
      ],
    ];

    return $data;
  }

}
