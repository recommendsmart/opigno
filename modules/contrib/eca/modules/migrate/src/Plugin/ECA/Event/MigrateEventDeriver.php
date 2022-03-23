<?php

namespace Drupal\eca_migrate\Plugin\ECA\Event;

use Drupal\eca\Plugin\ECA\Event\EventDeriverBase;

/**
 *
 */
class MigrateEventDeriver extends EventDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(): array {
    return MigrateEvent::actions();
  }

}
