<?php

namespace Drupal\beacon\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLink;

/**
 * Field handler to present a link to edit an entity.
 *
 * This is a duplicate of the plugin in Views but it does not include the
 * destination link because it breaks with AJAX.
 * @see https://www.drupal.org/project/drupal/issues/2828733
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_link_edit_no_destination")
 */
class EntityLinkEditNoDestination extends EntityLink {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'edit-form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('edit');
  }

}
