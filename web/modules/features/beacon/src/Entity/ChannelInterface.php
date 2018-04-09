<?php

namespace Drupal\beacon\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Channel entities.
 *
 * @ingroup beacon
 */
interface ChannelInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

}
