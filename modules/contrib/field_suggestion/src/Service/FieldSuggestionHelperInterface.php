<?php

namespace Drupal\field_suggestion\Service;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Interface FieldSuggestionHelperInterface.
 *
 * @package Drupal\field_suggestion\Service
 */
interface FieldSuggestionHelperInterface {

  /**
   * The hook name.
   */
  const HOOK = 'field_suggestion_ignore';

  /**
   * FieldSuggestionHelper constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository
  );

  /**
   * Gets field values that should be excluded from the suggestions list.
   *
   * @param $entity_type_id
   *   The entity type ID.
   * @param $field_name
   *   The field name.
   *
   * @return string[]
   *   The values list.
   */
  public function ignored($entity_type_id, $field_name);

  /**
   * Create a bundle.
   *
   * @param string $field_type
   *   The field type.
   */
  public function bundle($field_type);

}
