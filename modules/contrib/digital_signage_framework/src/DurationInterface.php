<?php

namespace Drupal\digital_signage_framework;

interface DurationInterface {

  /**
   * Returns the offset for critical entities.
   *
   * @return int
   */
  public function getOffsetForCritical(): int;

  /**
   * Returns the offset for the given complexity type.
   *
   * @param string $complexityType
   *
   * @return int
   */
  public function getOffsetByComplexity($complexityType): int;

  /**
   * Returns the duration for the given complexity type.
   *
   * @param string $complexityType
   *
   * @return int
   */
  public function getDurationByComplexity($complexityType): int;

}
