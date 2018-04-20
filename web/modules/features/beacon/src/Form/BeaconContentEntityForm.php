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

    $form = parent::buildForm($form, $form_state);

    // Determine the url to use for the cancel link.
    /*
    if ($parent_entity = $entity->getParent()) {
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
    */

    return $form;
  }

  /**
   * Helper function to add details wrappers to a form.
   *
   * @param array &$form
   *   The form to alter.
   * @param $wrapper
   *   The wrapper ID prefix.
   * @param $title
   *   The title to add to the details element.
   * @param array $key
   *   An array of form keys to add to the details element.
   */
   function addDetails(array &$form, $wrapper, $title, array $keys) {
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
