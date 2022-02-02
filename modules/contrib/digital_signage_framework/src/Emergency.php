<?php

namespace Drupal\digital_signage_framework;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\digital_signage_framework\SequenceItem;

class Emergency {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Renderer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected function queryAll(): array {
    $entities = [];
    try {
      $query = $this->entityTypeManager
        ->getStorage('digital_signage_content_setting')
        ->getQuery()
        ->condition('status', 1)
        ->condition('emergencymode', 1);
      /** @var \Drupal\digital_signage_framework\ContentSettingInterface $item */
      foreach ($this->entityTypeManager->getStorage('digital_signage_content_setting')
                 ->loadMultiple($query->execute()) as $item) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        if (($entity = $this->entityTypeManager->getStorage($item->getReverseEntityType())
            ->load($item->getReverseEntityId())) &&
          (!$entity->hasField('status') || $entity->get('status')->value)) {
          $entities[] = $entity;
        }
      }
    }
    catch (PluginException $e) {
      // TODO: Log this exception.
    }
    return $entities;
  }

  /**
   * @return array
   */
  public function all(): array {
    $entities = [];
    foreach ($this->queryAll() as $entity) {
      $entities[] = (new SequenceItem(
        $entity->get('digital_signage')->getValue()[0]['target_id'],
        $entity->id(),
        $entity->getEntityTypeId(),
        $entity->bundle(),
        $entity->label(),
        0,
        FALSE
      ))->toArray();
    }
    return $entities;
  }

  /**
   * @return array
   */
  public function allForSelect(): array {
    $entities = [];
    foreach ($this->queryAll() as $entity) {
      $entities[implode('/', [$entity->getEntityTypeId(), $entity->id()])] = $entity->label();
    }
    return $entities;
  }

}
