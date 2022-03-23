<?php

namespace Drupal\eca_misc\Plugin\ECA\Event;

use Drupal\eca\Plugin\ECA\Event\EventDeriverBase;

/**
 *
 */
class DrupalCoreEventDeriver extends EventDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(): array {
    return DrupalCoreEvent::actions();
  }

}
