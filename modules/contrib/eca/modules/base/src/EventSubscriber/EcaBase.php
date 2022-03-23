<?php

namespace Drupal\eca_base\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase as EcaBaseSubscriber;
use Drupal\eca_base\Plugin\ECA\Event\BaseEvent;

/**
 * ECA base event subscriber.
 */
class EcaBase extends EcaBaseSubscriber {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (BaseEvent::actions() as $action) {
      $events[$action['drupal_id']][] = ['onEvent'];
    }
    return $events;
  }

}
