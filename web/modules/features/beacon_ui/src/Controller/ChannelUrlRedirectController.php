<?php

namespace Drupal\beacon_ui\Controller;

use Drupal\beacon\Entity\ChannelInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Access\AccessResult;

/**
 * Class ChannelUrlRedirectController.
 *
 * Redirect the user to the channel's URL.
 */
class ChannelUrlRedirectController extends ControllerBase {

  /**
   * Redirect the user to the channel's URL.
   *
   * @param \Drupal\beacon\Entity\ChannelInterface $channel
   *   The channel entity.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   A redirect response.
   */
  public function redirectToChannelUrl(ChannelInterface $channel) {
    return new TrustedRedirectResponse($channel->url->first()->getUrl()->toString());
  }

  /**
   * Access callback for the redirection.
   *
   * @param \Drupal\beacon\Entity\ChannelInterface $channel
   *   The channel entity.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An access result.
   */
  public function redirectAccess(ChannelInterface $channel) {
    // TODO: This stops working after access is denied.
    return AccessResult::allowedIf(!$channel->url->isEmpty())
      ->andIf($channel->access('view', NULL, TRUE))
      ->cachePerUser()
      ->addCacheableDependency($channel);
  }

}
