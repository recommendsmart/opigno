<?php

namespace Drupal\digital_signage_framework;

interface WeightInterface {

  /**
   * Returns the weight for the given priority.
   *
   * @param int $priority
   *
   * @return int
   */
  public function getWeightByPriority($priority): int;

}
