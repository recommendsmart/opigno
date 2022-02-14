<?php

namespace Drupal\field_suggestion\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class FieldSuggestionHelper.
 *
 * @package Drupal\field_suggestion\Service
 */
class FieldSuggestionHelper implements FieldSuggestionHelperInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
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

}
