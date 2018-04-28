<?php

namespace Drupal\beacon;

use Drupal\beacon\Entity\EventInterface;

/**
 * Interface AlertDispatcherInterface.
 */
interface AlertDispatcherInterface {

  /**
   * Dispatch alerts for a given event.
   *
   * @param \Drupal\beacon\Entity\EventInterface $event
   *   An event entity.
   *
   * @return bool|int
   *   The amount of alerts dispatched, otherwise FALSE if there were none.
   */
  public function dispatch(EventInterface $event);

}
