<?php

namespace Drupal\digital_signage_framework;

/**
 * Interface for digital_signage_schedule_generator plugins.
 */
interface ScheduleGeneratorInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label(): string;

  /**
   * Generates the actual schedule.
   *
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   * @param \Drupal\digital_signage_framework\Entity\ContentSetting[] $contentSettings
   *
   * @return \Drupal\digital_signage_framework\SequenceItem[]
   */
  public function generate(DeviceInterface $device, array $contentSettings): array;

}
