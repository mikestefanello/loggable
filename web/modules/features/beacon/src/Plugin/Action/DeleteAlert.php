<?php

namespace Drupal\beacon\Plugin\Action;

/**
 * Provides an action for deleting alert entities.
 *
 * @Action(
 *   id = "alert_delete_action",
 *   label = @Translation("Delete alert"),
 *   type = "alert"
 * )
 */
class DeleteAlert extends DeleteEntityBase {

}
