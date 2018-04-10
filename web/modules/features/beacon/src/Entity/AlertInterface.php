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

}
