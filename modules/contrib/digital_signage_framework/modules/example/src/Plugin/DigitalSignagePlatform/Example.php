<?php

namespace Drupal\digital_signage_example\Plugin\DigitalSignagePlatform;

use Drupal\digital_signage_framework\DeviceInterface;
use Drupal\digital_signage_framework\Entity\Device;
use Drupal\digital_signage_framework\PlatformPluginBase;

/**
 * Plugin implementation of the digital_signage_platform.
 *
 * @DigitalSignagePlatform(
 *   id = "example",
 *   label = @Translation("Example"),
 *   description = @Translation("Provides an example platform.")
 * )
 */
class Example extends PlatformPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init() {
    // Nothing to do for now.
  }

  /**
   * {@inheritdoc}
   */
  public function scheduleBaseFields(array &$fields) {
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformDevices(): array {
    $this->messenger->addStatus('Receiving example devices');

    $deviceEntities = [];

    foreach ($this->configFactory->get('digital_signage_example.settings')->get('devices') as $device) {
      $values = [
        'bundle' => $this->getPluginId(),
        'extid' => $device['id'],
        'title' => $device['name'],
        'status' => TRUE,
        'description' => $device['name'],
        'segments' => [],
      ];
      if (!empty($device['orientation']) && !empty($device['orientation']['width']) && !empty($device['orientation']['height'])) {
        $values['size'] = [
          'width' => $device['orientation']['width'],
          'height' => $device['orientation']['height'],
        ];
      }
      /** @var \Drupal\digital_signage_framework\DeviceInterface $deviceEntity */
      $deviceEntity = Device::create($values);
      $deviceEntities[] = $deviceEntity;
      if (!empty($device['segments'])) {
        foreach ($device['segments'] as $segment) {
          $deviceEntity->addSegment($segment);
        }
      }
    }

    return $deviceEntities;
  }

  /**
   * {@inheritdoc}
   */
  public function pushSchedule(DeviceInterface $device, bool $debug, bool $reload_assets, bool $reload_content) {
    // TODO: Implement pushSchedule() method.
  }

  /**
   * {@inheritdoc}
   */
  public function pushConfiguration(DeviceInterface $device, bool $debug, bool $reload_schedule, bool $reload_assets, bool $reload_content) {
    // TODO: Implement reloadSchedule() method.
  }

  /**
   * {@inheritdoc}
   */
  public function setEmergencyMode(DeviceInterface $device, string $entity_type, int $entity_id) {
    // TODO: Implement setEmergencyMode() method.
  }

  /**
   * {@inheritdoc}
   */
  public function disableEmergencyMode(DeviceInterface $device) {
    // TODO: Implement disableEmergencyMode() method.
  }

  /**
   * {@inheritdoc}
   */
  public function debugDevice(DeviceInterface $device) {
    // TODO: Implement debugDevice() method.
  }

  /**
   * {@inheritdoc}
   */
  public function showDebugLog(DeviceInterface $device) {
    // TODO: Implement showDebugLog() method.
  }

  /**
   * {@inheritdoc}
   */
  public function showErrorLog(DeviceInterface $device) {
    // TODO: Implement showErrorLog() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getScreenshot(DeviceInterface $device, $refresh = FALSE): array {
    // TODO: Implement getScreenshot() method.
    return [];
  }

}
