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
    foreach (DrupalCoreEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    foreach (KernelEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    foreach (RoutingEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    return $events;
  }

}
