<?php

namespace Drupal\eca\Plugin\ECA\Event;

use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Plugin\EcaBase;

/**
 * Base class for ECA event plugins.
 */
abstract class EventBase extends EcaBase implements EventInterface {

  /**
   * {@inheritdoc}
   */
  final public function eventClass(): string {
    return $this->pluginDefinition['event_class'];
  }

  /**
   * {@inheritdoc}
   */
  final public function eventName(): string {
    return $this->pluginDefinition['event_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function lazyLoadingWildcard(string $eca_config_id, EcaEvent $ecaEvent): string {
    // By default return a small wildcard that should match up for every event
    // that is of the class as returned by ::drupalEventClass.
    return '*';
  }

}
