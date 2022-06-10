<?php

namespace Drupal\eca\Event;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for content entity related events.
 */
interface ContentEntityEventInterface {

  /**
   * Get the entity which was involved in the entity event.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The content entity.
   */
  public function getEntity(): EntityInterface;

}
