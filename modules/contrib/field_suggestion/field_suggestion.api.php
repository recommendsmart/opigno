<?php

/**
 * @file
 * Hooks for the Field Suggestion module.
 */

/**
 * @addtogroup hooks
 * @{
 */

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\field_suggestion\FieldSuggestionInterface;

/**
 * Provides field values that should be excluded from the suggestions list.
 *
 * @param $entity_type_id
 *   The entity type ID.
 * @param $field_name
 *   The field name.
 *
 * @return array
 *   The values list.
 *
 * @see \Drupal\field_suggestion\Service\FieldSuggestionHelper::ignored()
 * @see hook_field_suggestion_ignore_alter()
 */
function hook_field_suggestion_ignore($entity_type_id, $field_name) {
  $items = [];

  if ($entity_type_id === 'node' && $field_name === 'revision_log_message') {
    $items = ['-//-', '...'];
  }

  return $items;
}

/**
 * Alter ignored values for the suggestions list.
 *
 * @param array $items
 *   An array of values is returned by hook_field_suggestion_ignore().
 * @param $entity_type_id
 *   The entity type ID.
 * @param $field_name
 *   The field name.
 *
 * @see hook_field_suggestion_ignore()
 */
function hook_field_suggestion_ignore_alter(array &$items, $entity_type_id, $field_name) {
  if (
    $entity_type_id === 'node' &&
    $field_name === 'revision_log_message' &&
    ($delta = array_search('-//-', $items)) !== FALSE
  ) {
    unset($items[$delta]);
  }
}

/**
 * Exclude a pinned suggestion for the selected entity.
 *
 * @param array $excluded_entities
 *   The excluded entities list.
 * @param \Drupal\Core\Entity\ContentEntityInterface $entity
 *   The entity object.
 *
 * @return bool
 *   TRUE if suggestion should be excluded.
 */
function hook_field_suggestion_exclude(
  array $excluded_entities,
  ContentEntityInterface $entity
) {
  return count(
    array_filter(
      array_unique(array_column($excluded_entities, 'target_type')),
      function ($entity_type_id) use ($entity) {
        return $entity_type_id === $entity->getEntityTypeId();
      }
    )
  ) > 0;
}

/**
 * @} End of "addtogroup hooks".
 */
