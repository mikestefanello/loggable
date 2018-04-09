<?php

namespace Drupal\beacon\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Base form for deleting beacon entities.
 *
 * @ingroup beacon
 */
class BeaconContentEntityDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->urlInfo('canonical');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    // Check for a parent.
    if ($parent = $this->getEntity()->getParent()) {
      // Redirect to the parent.
      return $parent->toUrl('canonical');
    }

    // Otherwise fall back to the front page.
    return Url::fromRoute('<front>');
  }

}
