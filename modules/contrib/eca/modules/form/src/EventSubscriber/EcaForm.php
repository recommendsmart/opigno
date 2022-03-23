<?php

namespace Drupal\eca_form\EventSubscriber;

use Drupal\eca\EventSubscriber\EcaBase;
use Drupal\eca_form\Plugin\ECA\Event\FormEvent;

/**
 * ECA event subscriber.
 */
class EcaForm extends EcaBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    foreach (FormEvent::actions() as $action) {
      $events[$action['drupal_id']][] = ['onEvent'];
    }
    return $events;
  }

}
