<?php

namespace Drupal\eca_base\Commands;

use Drupal\eca_base\BaseEvents;
use Drupal\eca_base\Event\CustomEvent;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class EcaBaseCommands extends DrushCommands {

  /**
   * Trigger custom event with given event ID.
   *
   * @usage eca:trigger:custom_event
   *   Trigger custom event with given event ID.
   *
   * @param string $id
   *   The id of the custom event to be triggered.
   *
   * @command eca:trigger:custom_event
   */
  public function triggerCustomEvent(string $id): void {
    $event = new CustomEvent($id, []);
    \Drupal::service('event_dispatcher')->dispatch($event, BaseEvents::CUSTOM);
  }

}
