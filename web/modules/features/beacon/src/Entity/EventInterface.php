<?php

namespace Drupal\beacon\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Event entities.
 *
 * @ingroup beacon
 */
interface EventInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Get the type.
   *
   * @return string|null
   *   The event type, or NULL, if no value exists.
   */
  public function getType();

  /**
   * Get the severity.
   *
   * @return string|null
   *   The event severity, or NULL, if no value exists.
   */
  public function getSeverity();

  /**
   * Get the message.
   *
   * @return string|null
   *   The event message, or NULL, if no value exists.
   */
  public function getMessage();

  /**
   * Get the user.
   *
   * Note that this is the user field, and not the Drupal user owner.
   *
   * @return string|null
   *   The event user, or NULL, if no value exists.
   */
  public function getUser();

  /**
   * Get the URL.
   *
   * Note that this is the URL field, and not the entity URL.
   *
   * @return \Drupal\Core\Url|null
   *   The event URL, or NULL, if no value exists.
   */
  public function getUrl();

  /**
   * Get the expiration time.
   *
   * @return int|null
   *   The event expiration timestamp, or NULL, if no value exists.
   */
  public function getExpiration();

}
