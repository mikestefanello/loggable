<?php

namespace Drupal\beacon\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

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

    $form = parent::buildForm($form, $form_state);

    // TODO: Add cancel links?
    return $form;
  }

  /**
   * Helper function to add details wrappers to a form.
   *
   * @param array &$form
   *   The form to alter.
   * @param string $wrapper
   *   The wrapper ID prefix.
   * @param mixed $title
   *   The title to add to the details element.
   * @param array $keys
   *   An array of form keys to add to the details element.
   */
  public function addDetails(array &$form, string $wrapper, $title, array $keys) {
    $weight = &drupal_static(__METHOD__, 0);

    $form["{$wrapper}_wrapper"] = [
      '#type' => 'details',
      '#title' => $title,
      '#open' => TRUE,
      '#weight' => $weight,
    ];

    foreach ($keys as $key) {
      $form["{$wrapper}_wrapper"][$key] = $form[$key];
      unset($form[$key]);
    }

    $weight++;
  }

}
