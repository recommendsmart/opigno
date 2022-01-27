<?php

namespace Drupal\digital_signage_framework;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\digital_signage_framework\Entity\ContentSetting;
use Drupal\digital_signage_framework\Entity\Device;

/**
 * Content event service.
 */
class ContentEvent {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs an Entity update service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManager $entity_type_manager, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return \Drupal\digital_signage_framework\ContentSettingInterface|false|null
   */
  private function loadSettings(EntityInterface $entity) {
    if (!($entity instanceof ContentEntityInterface)) {
      // Only deal with content entities.
      return FALSE;
    }

    /** @var ContentEntityInterface $entity */
    if (!$entity->hasField('digital_signage')) {
      // Only deal with entities that have the digital_signage field;
      return FALSE;
    }

    // Load the settings entity.
    $settingsTarget = $entity->get('digital_signage')->getValue();
    if (!isset($settingsTarget[0]['target_id'])) {
      // Might be missing in some circumstances.
      return FALSE;
    }
    /** @var \Drupal\digital_signage_framework\ContentSettingInterface $settings */
    $settings = ContentSetting::load($settingsTarget[0]['target_id']);
    return $settings;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function presave(EntityInterface $entity) {
    $settings = $this->loadSettings($entity);
    if ($settings === NULL) {
      $settings = ContentSetting::create([]);
      $settings->save();
      /** @var ContentEntityInterface $entity */
      $entity->set('digital_signage', $settings->id());
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function update(EntityInterface $entity) {
    $settings = $this->loadSettings($entity);
    if ($settings === FALSE) {
      return;
    }
    if ($settings === NULL) {
      // This shouldn't ever happen as the presave hook should have made sure,
      // that we always have a settings entity.
      // TODO: Write an exception to watchdog/logger.
      return;
    }
    /** @var ContentEntityInterface $entity */
    $entityIsPublished = $entity->hasField('status') && (bool) $entity->get('status')->value;
    $needsUpdate = FALSE;

    // Store reverse reference.
    if (!$settings->getReverseEntity()) {
      $settings
        ->setReverseEntity($entity)
        ->setReverseEntityStatus($entityIsPublished)
        ->save();
      // New content entity, schedule updates required.
      $needsUpdate = TRUE;
    }
    elseif ($settings->isReverseEntityEnabled() !== $entityIsPublished) {
      $settings
        ->setReverseEntityStatus($entityIsPublished)
        ->save();
      // Publishing status got changed, schedule updates required.
      $needsUpdate = TRUE;
    }
    elseif ($settings->hasChanged()) {
      $needsUpdate = TRUE;
    }

    if ($settings->isEnabled() && (empty($settings->getLabel()) || $settings->isAutoLabel()) && $settings->getLabel() !== $entity->label()) {
      $settings->setLabel($entity->label());
      $settings->save();
    }

    if (!$needsUpdate) {
      // No schedule update required.
      return;
    }

    $this->messenger
      ->addMessage(t('Click <a href="@url">here</a> for the Digital Signage preview.', [
        '@url' => Url::fromRoute('entity.digital_signage_device.collection')->toString(),
      ]));

    $deviceIds = $settings->getDeviceIds();
    $segmentIds = $settings->getSegmentIds();

    // Now it's time to find out on which devices the content should be published.
    $query = $this->entityTypeManager
      ->getStorage('digital_signage_device')
      ->getQuery()
      ->condition('status', 1);

    if ($deviceIds || $segmentIds) {
      $subGroup = $query->orConditionGroup();
      if ($deviceIds) {
        $subGroup->condition('id', $deviceIds, 'IN');
      }
      if ($segmentIds) {
        $subGroup->condition('segments.target_id', $segmentIds, 'IN');
      }
      $query->condition($subGroup);
    }

    // TODO: Remember devices, this content has been pubslished before on and
    //   update schedules for those too, where the content no longer gets
    //   published at.

    // Retrieve and update all devices.
    foreach ($query->execute() as $id) {
      /** @var \Drupal\digital_signage_framework\DeviceInterface $device */
      $device = Device::load($id);
      $device->scheduleUpdate();
    }
  }

}
