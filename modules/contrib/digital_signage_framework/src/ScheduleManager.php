<?php

namespace Drupal\digital_signage_framework;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\digital_signage_framework\DeviceInterface;
use Drupal\digital_signage_framework\PlatformPluginManager;
use Drupal\digital_signage_framework\Entity\Schedule;

class ScheduleManager {

  /**
   * @var \Drupal\digital_signage_framework\ScheduleGeneratorPluginManager
   */
  protected $generatorPluginManager;

  /**
   * @var \Drupal\digital_signage_framework\PlatformPluginManager
   */
  protected $platformPluginManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public function __construct(ScheduleGeneratorPluginManager $generator_plugin_manager, PlatformPluginManager $platform_plugin_manager, EntityTypeManager $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->generatorPluginManager = $generator_plugin_manager;
    $this->platformPluginManager = $platform_plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * @param int|null $deviceId
   * @param bool $all
   *
   * @return \Drupal\digital_signage_framework\DeviceInterface[]
   */
  protected function getDevices($deviceId = NULL, $all = FALSE): array {
    try {
      $deviceManager = $this->entityTypeManager->getStorage('digital_signage_device');
    }
    catch (InvalidPluginDefinitionException $e) {
      // Can be ignored.
      return [];
    }
    catch (PluginNotFoundException $e) {
      // Can be ignored.
      return [];
    }
    $query = $deviceManager->getQuery();
    if ($deviceId !== NULL) {
      $query->condition('id', $deviceId);
    }
    if (!$all) {
      $query->condition('needs_update', 1);
    }
    $ids = $query
      ->execute();
    return $deviceManager->loadMultiple($ids);
  }

  /**
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   * @param \Drupal\digital_signage_framework\ScheduleGeneratorInterface $plugin
   * @param bool $store
   * @param bool $force
   * @param string|null $entityType
   * @param int|null $entityId
   *
   * @return \Drupal\digital_signage_framework\ScheduleInterface
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createSchedule(DeviceInterface $device, ScheduleGeneratorInterface $plugin, bool $store, bool $force, $entityType = NULL, $entityId = NULL) {
    // Collect the content entities for this schedule
    $query = $this->entityTypeManager
      ->getStorage('digital_signage_content_setting')
      ->getQuery()
      ->condition('status', 1)
      ->condition('emergencymode', 0);
    if ($entityType === NULL) {
      $orQuery = $query->orConditionGroup()
        ->condition($query->andConditionGroup()
          ->notExists('devices.target_id')
          ->notExists('segments.target_id')
        )
        ->condition('devices.target_id', $device->id());
      if ($segmentIds = $device->getSegmentIds()) {
        $orQuery->condition('segments.target_id', $segmentIds, 'IN');
      }
      $query->condition($orQuery);
      // Ignore entities that have a predecessor.
      $query->notExists('predecessor');

      // Allow other modules to alter the query for entities.
      $this->moduleHandler->alter('digital_signage_schedule_generator_query', $query, $device);
    }
    else {
      $query
        ->condition('parent_entity__target_type', $entityType)
        ->condition('parent_entity__target_id', $entityId);
    }

    $contentSettings = [];
    $hashMap = [];
    /** @var \Drupal\digital_signage_framework\ContentSettingInterface $item */
    foreach ($this->entityTypeManager->getStorage('digital_signage_content_setting')->loadMultiple($query->execute()) as $item) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      if (($entity = $this->entityTypeManager->getStorage($item->getReverseEntityType())->load($item->getReverseEntityId())) &&
        (!$entity->hasField('status') || $entity->get('status')->value)) {
        $contentSettings[] = $item;
        $hash_item = [
          'type' => $entity->getEntityTypeId(),
          'id' => $entity->id(),
        ];
        if ($entity->hasField('changed')) {
          $hash_item['changed'] = $entity->get('changed')->value;
        }
        else if ($entity->hasField('created')) {
          $hash_item['created'] = $entity->get('created')->value;
        }
      $hashMap[] = $hash_item;
      }
    }

    // See if we already have a schedule with that.
    $scheduleHash = md5(json_encode($hashMap));
    try {
      /** @var \Drupal\digital_signage_framework\ScheduleInterface[] $schedules */
      $schedules = $this->entityTypeManager
        ->getStorage('digital_signage_schedule')
        ->loadByProperties([
          'hash' => $scheduleHash,
        ]);
    }
    catch (InvalidPluginDefinitionException $e) {
    }
    catch (PluginNotFoundException $e) {
    }
    if ($force || empty($schedules)) {
      // We don't have one yet, let's create a new one.
      $items = [];
      foreach ($plugin->generate($device, $contentSettings) as $sequenceItem) {
        $this->addItemAndSuccessors($items, $sequenceItem, $device);
      }
      /** @var \Drupal\digital_signage_framework\ScheduleInterface $schedule */
      $schedule = Schedule::create([
        'hash' => $scheduleHash,
        'items' => [$items],
      ]);
      if ($store) {
        $schedule->save();
      }
    }
    else {
      $schedule = reset($schedules);
    }

    // Store the schedule with the device if necessary.
    if ($store) {
      if ($force || !$device->getSchedule() || $device->getSchedule()->id() !== $schedule->id()) {
        $device->setSchedule($schedule);
        $schedule->needsPush(TRUE);
      }
      $device
        ->scheduleUpdateCompleted()
        ->save();
    }

    return $schedule;
  }

  /**
   * @param array $items
   * @param \Drupal\digital_signage_framework\SequenceItem $sequenceItem
   * @param DeviceInterface $device
   * @param int $level
   */
  private function addItemAndSuccessors(array &$items, SequenceItem $sequenceItem, DeviceInterface $device, $level = 0): void {
    $items[] = $sequenceItem->toArray();
    if ($level > 3) {
      // Avoid infinite loop.
      return;
    }
    $level++;
    foreach ($sequenceItem->getSuccessors($device) as $successor) {
      $this->addItemAndSuccessors($items, $successor, $device, $level);
    }
  }

  /**
   * @param int|null $deviceId
   * @param bool $force
   * @param bool $debug
   * @param bool $reload_assets
   * @param bool $reload_content
   * @param string|null $entityType
   * @param int|null $entityId
   */
  public function pushSchedules($deviceId = NULL, $force = FALSE, $debug = FALSE, $reload_assets = FALSE, $reload_content = FALSE, $entityType = NULL, $entityId = NULL) {
    try {
      /** @var \Drupal\digital_signage_framework\ScheduleGeneratorInterface $plugin */
      $plugin = $this->generatorPluginManager->createInstance('default');
      foreach ($this->getDevices($deviceId, $force) as $device) {
          $schedule = $this->createSchedule($device, $plugin, TRUE, $force, $entityType, $entityId);
          if ($schedule->needsPush()) {
            $this->platformPluginManager->pushSchedule($device, $debug, $reload_assets, $reload_content);
            $schedule->needsPush(FALSE);
          }
      }
    }
    catch (PluginException $e) {
      // TODO: Log this exception.
    }
    catch (EntityStorageException $e) {
      // TODO: Log this exception.
    }
  }

  /**
   * @param string|null $deviceId
   * @param bool $debug
   * @param bool $reload_schedule
   * @param bool $reload_assets
   * @param bool $reload_content
   */
  public function pushConfiguration(?string $deviceId, bool $debug, bool $reload_schedule, bool $reload_assets, bool $reload_content) {
    foreach ($this->getDevices($deviceId, TRUE) as $device) {
      $this->platformPluginManager->pushConfiguration($device, $debug, $reload_schedule, $reload_assets, $reload_content);
    }
  }

  /**
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   *
   * @return \Drupal\digital_signage_framework\ScheduleInterface|null
   */
  public function getSchedule(DeviceInterface $device) {
    try {
      /** @var \Drupal\digital_signage_framework\ScheduleGeneratorInterface $plugin */
      $plugin = $this->generatorPluginManager->createInstance('default');
      return $this->createSchedule($device, $plugin, FALSE, TRUE);
    }
    catch (PluginException $e) {
      // TODO: Log this exception.
    }
    catch (EntityStorageException $e) {
      // TODO: Log this exception.
    }
    return NULL;
  }

}
