<?php

namespace Drupal\beacon_ui\Routing;

use Drupal\user\Entity\User;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Modify routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Views with default contextual filters still expose the option to pass
    // in the argument via the URL (as {arg+0}). We don't want that.
    if ($route = $collection->get('view.beacon_channel_alerts.page_1')) {
      $route->setPath('/channel/{channel}/alerts');
    }

    // Override the user page redirect to redirect to our custom edit form.
    if ($route = $collection->get('user.page')) {
      $route->setDefault('_controller', 'Drupal\beacon_ui\Routing\RouteSubscriber::userPage');
    }
  }

  /**
   * Override of the user page controller (/user).
   *
   * This redirects the user to the user edit form.
   */
  public function userPage() {
    return new RedirectResponse(Url::fromRoute('beacon_ui.user_edit')->toString());
  }

}
