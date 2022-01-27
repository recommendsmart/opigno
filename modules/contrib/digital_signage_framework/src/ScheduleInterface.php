<?php

namespace Drupal\digital_signage_framework;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a schedule entity type.
 */
interface ScheduleInterface extends ContentEntityInterface {

  /**
   * Gets the schedule creation timestamp.
   *
   * @return int
   *   Creation timestamp of the schedule.
   */
  public function getCreatedTime(): int;

  /**
   * @return array
   */
  public function getItems(): array;

  /**
   * @param bool|null $flag
   *
   * @return bool
   */
  public function needsPush($flag = NULL): bool;

}
