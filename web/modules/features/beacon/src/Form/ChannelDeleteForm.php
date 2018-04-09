<?php

namespace Drupal\beacon\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting Channel entities.
 *
 * @ingroup beacon
 */
class ChannelDeleteForm extends BeaconContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();

    $form = parent::submitForm($form, $form_state);

    // Get the children of this entity.
    $children = [];

    foreach (['event', 'alert'] as $entity_type_id) {
      $children[$entity_type_id] = $this->entityManager
        ->getStorage($entity_type_id)
        ->getQuery()
        ->condition('channel', $entity->id())
        ->execute();
    }

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
          $batch['operations'][] = [['Drupal\beacon\Form\ChannelDeleteForm', 'childrenDeleteBatchOp'], [$type, $id]];
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
