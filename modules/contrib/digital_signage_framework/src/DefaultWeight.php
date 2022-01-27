<?php

namespace Drupal\digital_signage_framework;

use Drupal\Core\Config\ImmutableConfig;

class DefaultWeight implements WeightInterface {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * DefaultDuration constructor.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $settings
   */
  public function __construct(ImmutableConfig $settings) {
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeightByPriority($priority): int {
    switch ($priority) {
      case 1:
        return $this->settings->get('schedule.priority_weight.high');

      case 2:
        return $this->settings->get('schedule.priority_weight.normal');

      default:
        return $this->settings->get('schedule.priority_weight.low');

    }
  }

}
