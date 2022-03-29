<?php

namespace Drupal\eca_user\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase;
use Drupal\eca_user\Plugin\ECA\Event\UserEvent;

/**
 * ECA event subscriber.
 */
class EcaUser extends EcaBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (UserEvent::actions() as $action) {
      $events[$action['event_name']][] = ['onEvent'];
    }
    return $events;
  }

}
