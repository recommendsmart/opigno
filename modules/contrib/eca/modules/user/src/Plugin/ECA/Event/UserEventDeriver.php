<?php

namespace Drupal\eca_user\Plugin\ECA\Event;

use Drupal\eca\Plugin\ECA\Event\EventDeriverBase;

/**
 *
 */
class UserEventDeriver extends EventDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(): array {
    return UserEvent::actions();
  }

}
