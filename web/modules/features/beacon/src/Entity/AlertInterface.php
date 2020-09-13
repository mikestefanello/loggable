<?php

namespace Drupal\beacon\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Alert entities.
 *
 * @ingroup beacon
 */
interface AlertInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Set the alert as enabled.
   *
   * @return $this
   */
  public function setEnabled();

  /**
   * Set the alert as disabled.
   *
   * @return $this
   */
  public function setDisabled();

  /**
   * Returns whether or not the alert is enabled.
   *
   * @return bool
   *   TRUE if the alert is enabled, otherwise FALSE.
   */
  public function isEnabled();

  /**
   * Get the type.
   *
   * @return string|null
   *   The alert type, or NULL, if no value is set.
   */
  public function getType();

  /**
   * Get the settings.
   *
   * @return array
   *   The alert settings.
   */
  public function getSettings();

  /**
   * Set the settings.
   *
   * @param array $settings
   *   An array of settings.
   *
   * @return \Drupal\beacon\Entity\AlertInterface
   *   The called alert.
   */
  public function setSettings(array $settings);

  /**
   * Get the event types.
   *
   * @return array
   *   An array of event types.
   */
  public function getEventTypes();

}
