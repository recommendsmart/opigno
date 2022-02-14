<?php

/**
 * @file
 * Hooks for the Field Suggestion module.
 */

/**
 * @addtogroup hooks
 * @{
 */

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
 * @} End of "addtogroup hooks".
 */
