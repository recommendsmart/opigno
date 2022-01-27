<?php

namespace Drupal\digital_signage_framework;

use Drupal;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\media\Entity\MediaType;

class SequenceItem {

  private $id;
  private $type;
  private $entityId;
  private $entityType;
  private $entityBundle;
  private $entityLabel;
  private $duration;
  private $isDynamic;

  private $successors;

  /**
   * SequenceItem constructor.
   *
   * @param int $id
   * @param int $contentEntityId
   * @param string $contentEntityType
   * @param string $contentEntityBundle
   * @param string||NULL $contentEntityLabel
   * @param int $duration
   * @param bool $is_dynamic
   */
  public function __construct(int $id, int $contentEntityId, string $contentEntityType, string $contentEntityBundle, $contentEntityLabel, int $duration, bool $is_dynamic) {
    $this->id = $id;
    $this->entityId = $contentEntityId;
    $this->entityType = $contentEntityType;
    $this->entityBundle = $contentEntityBundle;
    $this->entityLabel = $contentEntityLabel ?? implode(' ', [$contentEntityType, $contentEntityId]);
    $this->duration = $duration;
    $this->isDynamic = $is_dynamic;
    $this->determineType();
  }

  /**
   * Returns a sequence item by the given entity and duration.
   *
   * @param \Drupal\digital_signage_framework\ContentSettingInterface $contentSetting
   * @param int $duration
   *
   * @return \Drupal\digital_signage_framework\SequenceItem
   */
  public static function create(ContentSettingInterface $contentSetting, int $duration) : SequenceItem {
    return new SequenceItem(
      $contentSetting->id(),
      $contentSetting->getReverseEntityId(),
      $contentSetting->getReverseEntityType(),
      $contentSetting->getReverseEntityBundle(),
      $contentSetting->label(),
      $duration,
      $contentSetting->isDynamic()
    );
  }

  /**
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   *
   * @return array
   */
  public function getSuccessors(DeviceInterface $device): array {
    if ($this->successors === NULL) {
      $this->successors = [];
      $entityTypeManager = Drupal::entityTypeManager();
      try {
        $query = $entityTypeManager
          ->getStorage('digital_signage_content_setting')
          ->getQuery()
          ->condition('status', 1)
          ->condition('emergencymode', 0);
        $orQuery = $query->orConditionGroup()
          ->condition($query->andConditionGroup()
            ->notExists('devices.target_id')
            ->notExists('segments.target_id')
          )
          ->condition('devices.target_id', $device->id());
        if ($segmentIds = $device->getSegmentIds()) {
          $orQuery->condition('segments.target_id', $segmentIds, 'IN');
        }
        $query
          ->condition($orQuery)
          ->condition('predecessor', $this->id);
        /** @var \Drupal\digital_signage_framework\ContentSettingInterface $item */
        foreach ($entityTypeManager->getStorage('digital_signage_content_setting')->loadMultiple($query->execute()) as $item) {
          $this->successors[] = self::create($item, $this->duration);
        }
      }
      catch (InvalidPluginDefinitionException $e) {
        // Can be ignored.
      }
      catch (PluginNotFoundException $e) {
        // Can be ignored.
      }
    }
    return $this->successors;
  }

  /**
   * Determine content type.
   */
  protected function determineType(): void {
    $this->type = 'html';
    if ($this->entityType === 'media') {
      /** @var \Drupal\media\MediaTypeInterface $mediaType */
      $mediaType = MediaType::load($this->entityBundle);
      if (($fieldDefinition = $mediaType->getSource()->getSourceFieldDefinition($mediaType)) &&
          $fieldDefinition->getSetting('handler')) {
        if ($mediaType->getSource()->getPluginId() === 'file' && strpos($mediaType->id(), 'video') !== FALSE) {
          $this->type = 'video';
        }
        elseif (strpos($mediaType->getSource()->getPluginId(), 'video') === FALSE) {
          if ($mediaType->getSource()->getPluginId() !== 'svg') {
            $this->type = 'image';
          }
        }
        else {
          $this->type = 'video';
        }
      }
    }
  }

  /**
   * @return int
   */
  public function id(): int {
    return $this->id;
  }

  /**
   * @return array
   */
  public function toArray(): array {
    return [
      'type' => $this->getType(),
      'entity' => [
        'type' => $this->getEntityType(),
        'id' => $this->getEntityId(),
      ],
      'label' => $this->getEntityLabel(),
      'duration' => $this->getDuration(),
      'dynamic' => $this->getIsDynamic(),
    ];
  }

  /**
   * @return mixed
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Returns the content entity ID.
   *
   * @return int
   */
  public function getEntityId(): int {
    return $this->entityId;
  }

  /**
   * Returns the content entity type ID.
   *
   * @return string
   */
  public function getEntityType(): string {
    return $this->entityType;
  }

  /**
   * Returns the content entity label.
   *
   * @return string
   */
  public function getEntityLabel(): string {
    return $this->entityLabel;
  }

  /**
   * Returns the duration in seconds to display.
   *
   * @return int
   */
  public function getDuration(): int {
    return $this->duration;
  }

  /**
   * Returns TRUE if the content is dynamic or FALSE otherwise.
   *
   * @return bool
   */
  public function getIsDynamic(): bool {
    return $this->isDynamic;
  }

}
