<?php

namespace Drupal\digital_signage_framework;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\digital_signage_framework\Entity\ContentSetting;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Entity update service.
 */
class EntityUpdate {

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
   * @var \Drupal\digital_signage_framework\EntityFieldUpdate
   */
  protected $entityFieldUpdate;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\digital_signage_framework\EntityTypes
   */
  protected $entityTypesService;

  /**
   * @var ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs an Entity update service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager
   *   The entity definition update manager.
   * @param \Drupal\digital_signage_framework\EntityFieldUpdate $entity_field_update
   *   The entity field update manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity bundle manager.
   * @param \Drupal\digital_signage_framework\EntityTypes $entity_types_service
   *   The entity type service.
   * @param ModuleHandlerInterface $module_handler
   *   The Drupal container module handler.
   */
  public function __construct(EntityTypeManager $entity_type_manager, EntityDefinitionUpdateManagerInterface $update_manager, EntityFieldUpdate $entity_field_update, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypes $entity_types_service, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->updateManager = $update_manager;
    $this->entityFieldUpdate = $entity_field_update;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityTypesService = $entity_types_service;
    $this->moduleHandler = $module_handler;
  }

  /**
   * @return array
   */
  public function addExtraFields(): array {
    $extra = [];
    $bundles = $this->entityTypeBundleInfo->getAllBundleInfo();
    /** @var \Drupal\Core\Entity\EntityTypeInterface $definition */
    foreach ($this->entityTypeManager->getDefinitions() as $definition) {
      if (($definition instanceof ContentEntityTypeInterface) &&
        isset($bundles[$definition->id()]) &&
        ($definition->id() !== 'digital_signage_content_setting') &&
        !in_array($definition->id(), $this->entityTypesService->allDisabledIds(), TRUE)) {
        foreach ($bundles[$definition->id()] as $bundle => $def) {
          $extra[$definition->id()][$bundle]['display']['digital_signage_label'] = [
            'label' => t('Digital Signage Label'),
            'weight' => 0,
            'visible' => FALSE,
          ];
          $extra[$definition->id()][$bundle]['display']['digital_signage_label_fit'] = [
            'label' => t('Digital Signage Label (fit text)'),
            'weight' => 0,
            'visible' => FALSE,
          ];
          if ($this->moduleHandler->moduleExists('endroid_qr_code')) {
            $extra[$definition->id()][$bundle]['display']['digital_signage_qr_self'] = [
              'label' => t('QR Code'),
              'weight' => 0,
              'visible' => FALSE,
            ];
          }
        }
      }
    }
    return $extra;
  }

  /**
   * @return \Drupal\Core\Field\BaseFieldDefinition
   */
  public function fieldDefinition(): BaseFieldDefinition {
    return BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Digital signage'))
      ->setSetting('target_type', 'digital_signage_content_setting')
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_simple',
        'settings' => [
          'form_mode' => 'default',
          'label_singular' => '',
          'label_plural' => '',
          'collapsible' => TRUE,
          'collapsed' => TRUE,
          'override_labels' => FALSE,

        ],
        'weight' => 99,
      ])
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);
  }

  /** @noinspection PhpUnused */
  /**
   * Method description.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateExistingEntityTypes() {
    $field_definition = $this->fieldDefinition();
    /** @var \Drupal\Core\Entity\EntityTypeInterface $definition */
    foreach ($this->entityTypeManager->getDefinitions() as $definition) {
      if ($definition instanceof ContentEntityTypeInterface) {
        if ($definition->id() === 'digital_signage_content_setting') {
          $this->entityFieldUpdate->updateFields($definition->id(), ContentSetting::baseFieldDefinitions($definition));
          $this->ensureDisplayModes($definition);
        }
        elseif (!in_array($definition->id(), $this->entityTypesService->allDisabledIds(), TRUE)) {
          $this->updateManager->installFieldStorageDefinition('digital_signage', $definition->id(), $definition->getProvider(), $field_definition);
        }
      }
    }
  }

  /**
   * @param $entity_type_id
   * @param $machine_name
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function existsDisplayMode($entity_type_id, $machine_name): bool {
    return (bool) $this->entityTypeManager
      ->getStorage('entity_view_mode')
      ->getQuery()
      ->condition('id', $entity_type_id . '.' . $machine_name)
      ->execute();
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function ensureDisplayModes(ContentEntityTypeInterface $entity_type) {
    foreach (['landscape', 'portrait'] as $type) {
      $machine_name = 'digital_signage_' . $type;
      if ($this->existsDisplayMode($entity_type->id(), $machine_name)) {
        // Display mode already exists.
        continue;
      }
      $displayMode = EntityViewMode::create([
        'id' => $entity_type->id() . '.' . $machine_name,
        'label' => 'Digital signage ' . $type,
        'targetEntityType' => $entity_type->id(),
      ]);
      $displayMode->save();
    }
  }

}
