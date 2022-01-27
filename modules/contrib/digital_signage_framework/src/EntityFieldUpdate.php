<?php

namespace Drupal\digital_signage_framework;

use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Exception;

/**
 * Entity update service.
 */
class EntityFieldUpdate {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $updateManager;

  /**
   * Constructs an Entity update service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager
   *   The entity definition update manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager, EntityDefinitionUpdateManagerInterface $update_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->updateManager = $update_manager;
  }

  /**
   * @param $entity_type_id
   * @param $field_definitions
   */
  public function updateFields($entity_type_id, $field_definitions) {
    $changes = $this->updateManager->getChangeList();
    if (!isset($changes[$entity_type_id]['field_storage_definitions'])) {
      // Nothing to do.
      return;
    }
    $fieldUpdates = $changes[$entity_type_id]['field_storage_definitions'];

    try {
      $entityTypeDefinition = $this->entityTypeManager->getDefinition($entity_type_id);
      foreach ($field_definitions as $id => $baseFieldDefinition) {
        if (!isset($fieldUpdates[$id])) {
          // Nothing to do.
          continue;
        }
        switch ($fieldUpdates[$id]) {
          case EntityDefinitionUpdateManagerInterface::DEFINITION_CREATED:
            $this->updateManager->installFieldStorageDefinition($id, $entityTypeDefinition->id(), $entityTypeDefinition->getProvider(), $baseFieldDefinition);
            break;

          case EntityDefinitionUpdateManagerInterface::DEFINITION_UPDATED:
            $this->updateManager->updateFieldStorageDefinition($baseFieldDefinition);
            break;

          case EntityDefinitionUpdateManagerInterface::DEFINITION_DELETED:
            $this->updateManager->uninstallFieldStorageDefinition($baseFieldDefinition);
            break;

        }
      }
    }
    catch (Exception $ex) {
      // Ignore
    }
  }

}
