<?php

namespace Drupal\digital_signage_framework;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a device entity type.
 */
interface DeviceInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Get the corresponding plugin for the device.
   *
   * @return \Drupal\digital_signage_framework\PlatformInterface
   */
  public function getPlugin(): PlatformInterface;

  /**
   * @param bool $debug
   * @param bool $reload_assets
   * @param bool $reload_content
   *
   * @return array
   */
  public function getApiSpec($debug = FALSE, $reload_assets = FALSE, $reload_content = FALSE): array;

  /**
   * Gets the device external ID.
   *
   * @return string
   *   External ID of the device.
   */
  public function extId(): string;

  /**
   * Gets the device title.
   *
   * @return string
   *   Title of the device.
   */
  public function getTitle(): string;

  /**
   * Sets the device title.
   *
   * @param string $title
   *   The device title.
   *
   * @return \Drupal\digital_signage_framework\DeviceInterface
   *   The called device entity.
   */
  public function setTitle($title): DeviceInterface;

  /**
   * Gets the device creation timestamp.
   *
   * @return int
   *   Creation timestamp of the device.
   */
  public function getCreatedTime(): int;

  /**
   * Returns the device status.
   *
   * @return bool
   *   TRUE if the device is enabled, FALSE otherwise.
   */
  public function isEnabled(): bool;

  /**
   * Sets the device status.
   *
   * @param bool $status
   *   TRUE to enable this device, FALSE to disable.
   *
   * @return \Drupal\digital_signage_framework\DeviceInterface
   *   The called device entity.
   */
  public function setStatus($status): DeviceInterface;

  /**
   * Add a segment to the device.
   *
   * @param string $segment
   *
   * @return bool
   *   TRUE if a new segment got added to the device entity.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addSegment($segment): bool;

  /**
   * @return int[]
   */
  public function getSegmentIds(): array;

  /**
   * Returns TRUE if the device needs a schedule update, FALSE otherwise.
   *
   * @return bool
   */
  public function needsScheduleUpdate(): bool;

  /**
   * Schedule update for this devices's schedule and save the device.
   *
   * @return \Drupal\digital_signage_framework\DeviceInterface
   *   The called device entity.
   */
  public function scheduleUpdate(): DeviceInterface;

  /**
   * Update for this devices's schedule completed.
   *
   * @return \Drupal\digital_signage_framework\DeviceInterface
   *   The called device entity.
   */
  public function scheduleUpdateCompleted(): DeviceInterface;

  /**
   * Gets the active schedule for this device.
   *
   * @param bool $stored
   *   Whether to receive the stored schedule or a temporary one.
   *
   * @return \Drupal\digital_signage_framework\ScheduleInterface|null
   */
  public function getSchedule($stored = TRUE);

  /**
   * Sets the active schedule for this device.
   *
   * @param \Drupal\digital_signage_framework\ScheduleInterface $schedule
   *
   * @return \Drupal\digital_signage_framework\DeviceInterface
   *   The called device entity.
   */
  public function setSchedule($schedule): DeviceInterface;

  /**
   * Gets the resolution width.
   *
   * @return int
   */
  public function getWidth(): int;

  /**
   * Gets the resolution height.
   *
   * @return int
   */
  public function getHeight(): int;

  /**
   * Get portrait or landscape as orientation string.
   *
   * @return string
   */
  public function getOrientation(): string;

  /**
   * Get the parent entity.
   *
   * @return ContentSettingInterface|null
   */
  public function getEmergencyEntity();

}
