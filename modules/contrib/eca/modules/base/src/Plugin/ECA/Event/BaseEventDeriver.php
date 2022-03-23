<?php

namespace Drupal\eca_base\Plugin\ECA\Event;

use Drupal\eca\Plugin\ECA\Event\EventDeriverBase;

/**
 *
 */
class BaseEventDeriver extends EventDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(): array {
    return BaseEvent::actions();
  }

}
