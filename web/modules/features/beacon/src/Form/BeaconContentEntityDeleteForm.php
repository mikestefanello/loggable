<?php

namespace Drupal\beacon\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

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

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, $additional_children = []) {
    $entity = $this->getEntity();

    $form = parent::submitForm($form, $form_state);

    // TODO: Delete children.
    return;

    // Get the children of this entity.
    $children = beacon_entity_get_children($entity->getEntityTypeId(), [$entity->id()]);

    // Add the additional children passed in.
    $children = array_merge($children, $additional_children);

    // Check if we have children to delete.
    if ($children) {
      // Use a batch to delete the children.
      $batch = [
        'title' => t('Processing delete request'),
        'operations' => [],
      ];

      // Add the children.
      foreach ($children as $type => $ids) {
        foreach ($ids as $id) {
          $batch['operations'][] = [['Drupal\beacon\Form\BeaconContentEntityDeleteForm', 'childrenDeleteBatchOp'], [$type, $id]];
        }
      }

      // Start the batch.
      batch_set($batch);
    }
  }

  /**
   * Batch operation callback to delete an entity.
   *
   * @see submitForm()
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   */
  public static function childrenDeleteBatchOp($entity_type_id, $entity_id) {
    \Drupal::entityTypeManager()
      ->getStorage($entity_type_id)
      ->load($entity_id)
      ->delete();
  }

}
