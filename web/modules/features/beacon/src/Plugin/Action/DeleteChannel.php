<?php

namespace Drupal\beacon\Plugin\Action;

/**
 * Provides an action for deleting channel entities.
 *
 * @Action(
 *   id = "channel_delete_action",
 *   label = @Translation("Delete channel"),
 *   type = "channel"
 * )
 */
class DeleteChannel extends DeleteEntityBase {

}
