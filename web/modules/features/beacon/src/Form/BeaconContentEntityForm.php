<?php

namespace Drupal\beacon\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller base class for Beacon content entity forms.
 *
 * @ingroup beacon
 */
class BeaconContentEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Check that the entity is new.
    if ($entity->isNew()) {
      // Check if the entity has a parent field.
      if ($parent = $entity->getParentReferenceFieldName()) {
        // Load the contextual entity.
        if ($parent_entity = $this->beacon->getContextualEntity($entity->getEntityTypeId(), TRUE)) {
          // Store the target entity in the parent field.
          $entity->set($parent, ['target_id' => $parent_entity->id()]);

          // Add a cancel link which points back to the parent.
          $form['actions']['cancel'] = [
            '#type' => 'link',
            '#title' => t('Cancel'),
            '#url' => $parent_entity->toUrl(),
            '#weight' => 100,
          ];
        }
      }
    }

    $form = parent::buildForm($form, $form_state);

    // Determine the url to use for the cancel link.
    if (isset($parent_entity)) {
      $cancel_url = $parent_entity->toUrl();
    }
    elseif (!$entity->isNew()) {
      $cancel_url = $entity->toUrl();
    }

    // Add a cancel link.
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => isset($cancel_url) ? $cancel_url : NULL,
      '#weight' => 100,
      '#access' => isset($cancel_url),
    ];
    $form['actions']['#weight'] = 300;

    return $form;
  }

}
