<?php

namespace Drupal\arch_downloadable_product\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.field_config.product_field_delete_form')) {
      $route->setRequirements([
        '_custom_access_check' => 'FALSE',
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Try to set a low priority to ensure that all routes are already added.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -2000];
    return $events;
  }

}
