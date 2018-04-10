<?php

namespace Drupal\beacon\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\beacon\Entity\EventInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Alert type plugins.
 */
interface AlertTypeInterface extends PluginInspectionInterface {

  /**
   * Send an alert.
   *
   * @param \Drupal\beacon\Entity\EventInterface $event
   *   The event entity to send an alert for.
   *
   * @return bool
   *   TRUE if the alert was sent, otherwise FALSE.
   */
  public function send(EventInterface $event);

  /**
   * Return the settings for this plugin.
   *
   * @return array
   *   An array of settings.
   */
  public function getSettings();

  /**
   * Provide a configuration form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @return array
   *   A form array.
   */
  public function settingsForm(FormStateInterface $form_state);

  /**
   * Validate a configuration form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateSettingsForm(FormStateInterface $form_state);

  /**
   * Submission callback for a configuration form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitSettingsForm(FormStateInterface $form_state);

}
