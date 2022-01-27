<?php

namespace Drupal\digital_signage_framework;

use Drupal\Core\Config\ImmutableConfig;

class DefaultDuration implements DurationInterface {

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
  public function getOffsetForCritical(): int {
    return $this->settings->get('schedule.offsets.critical');
  }

  /**
   * {@inheritdoc}
   */
  public function getOffsetByComplexity($complexityType): int {
    return ($complexityType === 'complex') ? $this->settings->get('schedule.offsets.complex') : 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getDurationByComplexity($complexityType): int {
    return $this->settings->get('schedule.duration') * $this->getOffsetByComplexity($complexityType);
  }

}
