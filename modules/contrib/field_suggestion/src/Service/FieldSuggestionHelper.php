<?php

namespace Drupal\field_suggestion\Service;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class FieldSuggestionHelper.
 *
 * @package Drupal\field_suggestion\Service
 */
class FieldSuggestionHelper implements FieldSuggestionHelperInterface {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository
  ) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function ignored($entity_type_id, $field_name) {
    $items = $this->moduleHandler->invokeAll(self::HOOK, [
      $entity_type_id,
      $field_name,
    ]);

    $this->moduleHandler->alter(self::HOOK, $items, $entity_type_id, $field_name);

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function bundle($field_type) {
    $this->entityTypeManager->getStorage('field_suggestion_type')
      ->create(['id' => $field_type])
      ->save();

    $field_storage = $this->entityTypeManager->getStorage('field_storage_config')
      ->create([
        'type' => $field_type,
        'entity_type' => 'field_suggestion',
        'field_name' => 'field_suggestion_' . $field_type,
      ]);

    $field_storage->save();

    $this->entityTypeManager->getStorage('field_config')->create([
      'field_storage' => $field_storage,
      'bundle' => $field_type,
      'label' => 'Suggestion',
      'required' => TRUE,
    ])->save();

    $this->entityDisplayRepository->getFormDisplay('field_suggestion', $field_type)
      ->setComponent($field_storage->getName(), ['weight' => 0])
      ->save();
  }

}
