<?php

namespace Drupal\digital_signage_framework\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\digital_signage_framework\Emergency;
use Drupal\digital_signage_framework\Entity\Device;
use Drupal\digital_signage_framework\PlatformPluginManager;
use Drush\Commands\DrushCommands;
use InvalidArgumentException;

/**
 * A Drush commandfile for digital signage devices.
 */
class DeviceCommands extends DrushCommands {

  /**
   * @var \Drupal\digital_signage_framework\PlatformPluginManager
   */
  protected $pluginManager;

  /**
   * @var \Drupal\digital_signage_framework\Emergency
   */
  protected $emergency;

  /**
   * {@inheritdoc}
   */
  public function __construct(PlatformPluginManager $plugin_manager, Emergency $emergency) {
    parent::__construct();
    $this->pluginManager = $plugin_manager;
    $this->emergency = $emergency;
  }

  /**
   * Synchronize devices with all platforms.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   * @option platform
   *   Platform ID if only one should be synchronised, defaults to all.
   * @usage digital-signage-device:sync
   *   Synchronises all devices with all enabled platforms.
   *
   * @command digital-signage-device:sync
   * @aliases dsds
   */
  public function syncDevices($options = ['platform' => NULL]) {
    $this->pluginManager->syncDevices($options['platform']);
  }

  /**
   * List devices.
   *
   * @usage digital-signage-device:list
   *   List all devices.
   *
   * @command digital-signage-device:list
   * @table-style default
   * @field-labels
   *   id: ID
   *   label: Name
   *   extid: External ID
   *   platform: Platform
   *   status: Status
   *   slides: No of slides
   * @default-fields id,label,platform,extid,status,slides
   * @aliases dsdl
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields|void
   */
  public function listDevices(array $options = ['all' => FALSE, 'platform' => NULL, 'format' => 'table']) {
    $devices = [];
    /** @var \Drupal\digital_signage_framework\DeviceInterface $device */
    foreach (Device::loadMultiple() as $device) {
      if ($options['all'] || $device->isEnabled()) {
        if ($options['platform'] === NULL || $device->bundle() === $options['platform']) {
          $schedule = $device->getSchedule();
          $devices[] = [
            'id' => $device->id(),
            'label' => $device->label(),
            'extid' => $device->extId(),
            'platform' => $device->bundle(),
            'status' => $device->isEnabled(),
            'slides' => ($schedule === NULL) ? 0 : count($schedule->getItems()),
          ];
        }
      }
    }
    return new RowsOfFields($devices);
  }

  /**
   * Show debug log.
   *
   * @usage digital-signage-device:log:debug 17
   *   Show debug logs from device with id 17.
   *
   * @command digital-signage-device:log:debug
   * @param int $deviceId
   * @option record
   * @table-style default
   * @field-labels
   *   time: Date / Time
   *   message: Message
   * @default-fields time,message
   * @aliases dsdld
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields|void
   */
  public function showDebugLogs($deviceId, array $options = ['record' => NULL, 'format' => 'table']) {
    if ($device = $this->loadDevice($deviceId)) {
      if ($options['record'] !== NULL) {
        $record = $device->getPlugin()->getRecord($options['record']) ?? '';
        if (!is_scalar($record)) {
          $record = json_encode($record, JSON_PRETTY_PRINT);
        }
        $this->io()->block($record);
      }
      else {
        $rows = $device->getPlugin()->showDebugLog($device);
        return new RowsOfFields($rows);
      }
    }
  }

  /**
   * Show error log.
   *
   * @usage digital-signage-device:log:error 17
   *   Show error logs from device with id 17.
   *
   * @command digital-signage-device:log:error
   * @param int $deviceId
   * @option record
   * @table-style default
   * @field-labels
   *   time: Date / Time
   *   message: Message
   * @default-fields time,message
   * @aliases dsdle
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields|void
   */
  public function showErrorLogs($deviceId, array $options = ['record' => NULL, 'format' => 'table']) {
    if ($device = $this->loadDevice($deviceId)) {
      if ($options['record'] !== NULL) {
        $record = $device->getPlugin()->getRecord($options['record']) ?? '';
        if (!is_scalar($record)) {
          $record = json_encode($record, JSON_PRETTY_PRINT);
        }
        $this->io()->block($record);
      }
      else {
        $rows = $device->getPlugin()->showErrorLog($device);
        return new RowsOfFields($rows);
      }
    }
  }

  /**
   * Show slide change report.
   *
   * @usage digital-signage-device:report:slide 17
   *   Show slide change report from device with id 17.
   *
   * @command digital-signage-device:report:slide
   * @param int $deviceId
   * @table-style default
   * @field-labels
   *   time: Date / Time
   *   message: Slide ID
   * @default-fields time,message
   * @aliases dsdrs
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields|void
   */
  public function showSlideChangeReport($deviceId, array $options = ['format' => 'table']) {
    if ($device = $this->loadDevice($deviceId)) {
      $rows = $device->getPlugin()->showSlideReport($device);
      return new RowsOfFields($rows);
    }
  }

  /**
   * Turn on debugging for the given device.
   *
   * @usage digital-signage-device:debug:on 17
   *   Turn on debugging on device with id 17.
   *
   * @command digital-signage-device:debug
   * @param int $deviceId
   * @aliases dsdd
   */
  public function debug($deviceId) {
    if ($device = $this->loadDevice($deviceId)) {
      $device->getPlugin()->debugDevice($device);
    }
  }

  /**
   * Set emergency mode for the given device.
   *
   * @usage digital-signage-device:emergencymode:set 17
   *   Set emergency mode for device with id 17.
   *
   * @command digital-signage-device:emergencymode:set
   * @param int $deviceId
   * @aliases dsdes
   */
  public function setEmergencyMode($deviceId) {
    if ($device = $this->loadDevice($deviceId)) {
      if ($entities = $this->emergency->allForSelect()) {
        $choice = $this->io()->choice('Select the content for emergency mode', $entities);
        [$type, $id] = explode('/', $choice);
        $entity = \Drupal::entityTypeManager()->getStorage($type)->load($id);
        $device
          ->set('emergency_entity', $entity->get('digital_signage')->getValue()[0]['target_id'])
          ->save();
      }
      else {
        $this->io()->warning('No emergency content available.');
      }
    }
  }

  /**
   * Disable emergency mode on the given device.
   *
   * @usage digital-signage-device:emergencymode:disable 17
   *   Disable emergency mode on device with id 17.
   *
   * @command digital-signage-device:emergencymode:disable
   * @param int $deviceId
   * @aliases dsded
   */
  public function disableEmergencyMode($deviceId) {
    if ($device = $this->loadDevice($deviceId)) {
      $device
        ->set('emergency_entity', NULL)
        ->save();
    }
  }

  /**
   * @param int $deviceId
   * @param string|null $bundle
   *
   * @return \Drupal\digital_signage_framework\DeviceInterface
   *
   */
  protected function loadDevice($deviceId, $bundle = NULL) {
    /** @var \Drupal\digital_signage_framework\DeviceInterface $device */
    $device = Device::load($deviceId);
    if (empty($device)) {
      throw new InvalidArgumentException('Incorrect device ID');
    }
    if ($bundle !== NULL && $bundle !== $device->bundle()) {
      throw new InvalidArgumentException('Device is not the correct type: ' . $bundle);
    }
    return $device;
  }

}
