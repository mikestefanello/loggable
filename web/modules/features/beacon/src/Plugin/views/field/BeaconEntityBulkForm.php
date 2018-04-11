<?php

namespace Drupal\beacon\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines a beacon entity operations bulk form element.
 *
 * @ViewsField("beacon_entity_bulk_form")
 */
class BeaconEntityBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('Nothing selected.');
  }

}
