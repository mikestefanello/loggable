<?php

namespace Drupal\beacon\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Event edit forms.
 *
 * @ingroup beacon
 */
class EventForm extends BeaconContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\beacon\Entity\Channel */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created event %uuid.', [
          '%uuid' => $entity->uuid(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved event %uuid.', [
          '%uuid' => $entity->uuid(),
        ]));
    }
    $form_state->setRedirect('entity.event.canonical', ['event' => $entity->uuid()]);
  }

}
