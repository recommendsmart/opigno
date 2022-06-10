<?php

namespace Drupal\eca_config\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase;
use Drupal\eca_config\Plugin\ECA\Event\ConfigEvent;

/**
 * ECA config event subscriber.
 */
class EcaConfig extends EcaBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (ConfigEvent::definitions() as $definition) {
      $events[$definition['event_name']][] = ['onEvent'];
    }
    return $events;
  }

}
