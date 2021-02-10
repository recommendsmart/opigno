<?php

namespace Drupal\collection;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityInterface;

/**
 * The collection content manager service.
 */
class CollectionContentManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * CollectionContentManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get collection(s) to which this entity belongs.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The collections to which this entity belongs.
   */
  public function getCollectionsForEntity(EntityInterface $entity) {
    $collections = [];

    // Load all collection items that reference this entity.
    $collection_item_storage = $this->entityTypeManager->getStorage('collection_item');

    $collection_item_ids = $collection_item_storage->getQuery()
      ->condition('item__target_type', $entity->getEntityTypeId())
      ->condition('item__target_id', $entity->id())
      ->execute();

    $collection_items = $collection_item_storage->loadMultiple($collection_item_ids);

    if (count($collection_items) === 0) {
      return [];
    }

    foreach ($collection_items as $collection_item) {
      $collection = $collection_item->collection->entity;

      if (!$collection->access('update')) {
        continue;
      }

      $collections[$collection->id()] = $collection;
    }

    return $collections;
  }
}
