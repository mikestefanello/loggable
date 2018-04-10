<?php

namespace Drupal\beacon\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\beacon\Entity\EventInterface;

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

}
