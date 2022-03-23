<?php

namespace Drupal\eca_queue\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase;
use Drupal\eca_queue\Plugin\ECA\Event\QueueEventDeriver;

/**
 * ECA base event subscriber.
 */
class EcaQueue extends EcaBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (QueueEventDeriver::definitions() as $definition) {
      $events[$definition['drupal_id']][] = ['onEvent'];
    }
    return $events;
  }

}
