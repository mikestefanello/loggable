<?php

namespace Drupal\beacon\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Alert entities.
 */
class AlertViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['alert']['alert_bulk_form'] = [
      'title' => $this->t('Alert operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple alerts.'),
      'field' => [
        'id' => 'beacon_entity_bulk_form',
      ],
    ];

    return $data;
  }

}
