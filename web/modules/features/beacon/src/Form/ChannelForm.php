<?php

namespace Drupal\beacon\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Channel edit forms.
 *
 * @ingroup beacon
 */
class ChannelForm extends BeaconContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
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
        drupal_set_message($this->t('Created the %label channel.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label channel.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.channel.canonical', ['channel' => $entity->uuid()]);
  }

}
