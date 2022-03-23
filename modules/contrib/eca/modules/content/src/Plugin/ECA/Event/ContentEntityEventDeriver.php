<?php

namespace Drupal\eca_content\Plugin\ECA\Event;

use Drupal\eca\Plugin\ECA\Event\EventDeriverBase;

/**
 *
 */
class ContentEntityEventDeriver extends EventDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(): array {
    return ContentEntityEvent::actions();
  }

}
