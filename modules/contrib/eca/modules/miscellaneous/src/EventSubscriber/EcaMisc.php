<?php

namespace Drupal\eca_misc\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase;
use Drupal\eca_misc\Plugin\ECA\Event\DrupalCoreEvent;
use Drupal\eca_misc\Plugin\ECA\Event\KernelEvent;
use Drupal\eca_misc\Plugin\ECA\Event\RoutingEvent;

/**
 * ECA miscellaneous event subscriber.
 */
class EcaMisc extends EcaBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (DrupalCoreEvent::actions() as $action) {
      $events[$action['drupal_id']][] = ['onEvent'];
    }
    foreach (KernelEvent::actions() as $action) {
      $events[$action['drupal_id']][] = ['onEvent'];
    }
    foreach (RoutingEvent::actions() as $action) {
      $events[$action['drupal_id']][] = ['onEvent'];
    }
    return $events;
  }

}
