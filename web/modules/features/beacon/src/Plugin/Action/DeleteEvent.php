<?php

namespace Drupal\beacon\Plugin\Action;

/**
 * Provides an action for deleting event entities.
 *
 * @Action(
 *   id = "event_delete_action",
 *   label = @Translation("Delete event"),
 *   type = "event"
 * )
 */
class DeleteEvent extends DeleteEntityBase {

}
