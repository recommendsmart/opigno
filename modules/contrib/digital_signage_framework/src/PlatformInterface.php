<?php

namespace Drupal\digital_signage_framework;

use Drupal\digital_signage_framework\DeviceInterface;

/**
 * Interface for digital_signage_platform plugins.
 */
interface PlatformInterface {

  /**
   * Initialize the plugin.
   */
  public function init();

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label(): string;

  /**
   * @param string $id
   * @param mixed $value
   */
  public function storeRecord($id, $value);

  /**
   * @param string $id
   */
  public function deleteRecord($id);

  /**
   * @param string $id
   *
   * @return mixed
   */
  public function getRecord($id);

  /**
   * Provide a list of extra base fields required for the platform's schedule.
   *
   * @param array $fields
   */
  public function scheduleBaseFields(array &$fields);

  /**
   * Syncs devices of this platform.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function syncDevices();

  /**
   * Receive a list of all devices from the platform.
   *
   * @return \Drupal\digital_signage_framework\Entity\Device[]
   */
  public function getPlatformDevices(): array;

  /**
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   * @param bool $debug
   * @param bool $reload_assets
   * @param bool $reload_content
   */
  public function pushSchedule(DeviceInterface $device, bool $debug, bool $reload_assets, bool $reload_content);

  /**
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   * @param bool $debug
   * @param bool $reload_schedule
   * @param bool $reload_assets
   * @param bool $reload_content
   */
  public function pushConfiguration(DeviceInterface $device, bool $debug, bool $reload_schedule, bool $reload_assets, bool $reload_content);

  /**
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   * @param string $entity_type
   * @param int $entity_id
   */
  public function setEmergencyMode(DeviceInterface $device, string $entity_type, int $entity_id);

  /**
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   */
  public function disableEmergencyMode(DeviceInterface $device);

  /**
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   */
  public function debugDevice(DeviceInterface $device);

  /**
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   *
   * @return array
   */
  public function showDebugLog(DeviceInterface $device);

  /**
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   *
   * @return array
   */
  public function showErrorLog(DeviceInterface $device);

  /**
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   *
   * @return array
   */
  public function showSlideReport(DeviceInterface $device);

  /**
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   * @param bool $refresh
   *
   * @return array
   *   Key value pairs with "takenAt" and "uri" as the keys for a date and
   *   the screenshot uri.
   */
  public function getScreenshot(DeviceInterface $device, $refresh = FALSE): array;

}
