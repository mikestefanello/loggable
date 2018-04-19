<?php

namespace Drupal\beacon_ui\Routing;

use Drupal\user\Entity\User;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;

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

    // TODO
    return;

    // Switch user routes to use UUIDs.
    foreach (['canonical', 'edit_form', 'cancel_form'] as $link) {
      if ($route = $collection->get("entity.user.{$link}")) {
        $route->setOption('parameters', ['user' => ['type' => 'entity_uuid', 'entity_type_id' => 'user']]);
        $route->setRequirement('user', '[\d\w\-]+');
      }
    }

    // Override the user page redirect to support UUID.
    if ($route = $collection->get('user.page')) {
      $route->setDefault('_controller', 'Drupal\beacon\Routing\RouteSubscriber::userPage');
    }
  }

  /**
   * Override of the user page controller (/user).
   *
   * This redirects the user to the user canonical using the UUID.
   */
  public function userPage() {
    return new RedirectResponse(User::load(\Drupal::currentUser()->id())->url());
  }

}
