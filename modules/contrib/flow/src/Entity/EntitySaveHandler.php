<?php

namespace Drupal\flow\Entity;

use Drupal\flow\Flow;
use Drupal\flow\Helpers\EntityTypeManagerTrait;

/**
 * Handles the saving of multiple entities.
 */
class EntitySaveHandler {

  use EntityTypeManagerTrait;

  /**
   * The saving list threshold.
   *
   * When the number of entities in a saving list surpasses this value, then
   * saving will be performed.
   *
   * @var int
   */
  protected static int $threshold = 20;

  /**
   * Constructs a new entity save handler instance.
   *
   * @param int $threshold
   *   The saving list threshold.
   */
  public function __construct(int $threshold = 20) {
    static::$threshold = $threshold;
  }

  /**
   * Get the entity save handler service.
   *
   * @return \Drupal\flow\Entity\EntitySaveHandler
   *   The entity save handler.
   */
  public static function service(): EntitySaveHandler {
    return \Drupal::service('flow.entity_save_handler');
  }

  /**
   * Saves the given list of entities, if it makes sense to do so.
   *
   * Any entity that got saved will be removed from the given list.
   * This method should only be called when it's guaranteed that somewhere later
   * within the same PHP process ::ensureSaveAll() will be finally called.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] &$entities
   *   The list of entities to save.
   */
  public function saveIfRequired(array &$entities): void {
    if (count($entities) > static::$threshold) {
      $this->ensureSaveAll($entities);
    }
  }

  /**
   * Ensures that all entities are being saved that are in the given list.
   *
   * Any entity that is ensured to be saved (or got already saved) will be
   * removed from the given list. As this method ensures that all contained
   * entities are being saved, the given list will be empty.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] &$entities
   *   The list of entities to save.
   */
  public function ensureSaveAll(array &$entities): void {
    $ensured = [];
    // Treat entities laying in the Flow stack as already ensured for saving.
    foreach (Flow::$stack as &$stacked_entities) {
      foreach ($stacked_entities as $stacked_entity) {
        if (!in_array($stacked_entity, $ensured, TRUE)) {
          $ensured[] = $stacked_entity;
        }
      }
    }
    foreach ($entities as $i => $entity) {
      if (!in_array($entity, $ensured, TRUE)) {
        $entity->save();
        $ensured[] = $entity;
      }
      unset($entities[$i]);
    }
  }

}
