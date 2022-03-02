<?php

/**
 * @file
 * Post update functions for Field Suggestion.
 */

/**
 * Move pinned items to entities.
 */
function field_suggestion_post_update_pinned_to_entities(&$sandbox) {
  if (!isset($sandbox['total'])) {
    $sandbox['pinned'] = [];
    $pinned = \Drupal::state()->get('field_suggestion', []);

    foreach ($pinned as $entity_type_id => $fields) {
      foreach ($fields as $field_name => $items) {
        foreach ($items as $item) {
          $sandbox['pinned'][] = [
            'entity_type' => $entity_type_id,
            'field_name' => $field_name,
            'field_value' => $item,
          ];
        }
      }
    }

    if (($sandbox['total'] = count($sandbox['pinned'])) === 0) {
      return;
    }

    $sandbox['processed'] = 0;
    $sandbox['types'] = [];
  }

  $values = $sandbox['pinned'][$sandbox['processed']++];

  $entity_type_id = $values['entity_type'];
  $field_name = $values['field_name'];

  if (isset($sandbox['types'][$entity_type_id][$field_name])) {
    $values['type'] = $sandbox['types'][$entity_type_id][$field_name];
  }
  else {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $manager */
    $manager = \Drupal::service('entity_field.manager');

    $definitions = $manager->getBaseFieldDefinitions($entity_type_id);
    $values['type'] = $definitions[$field_name]->getType();
    $sandbox['types'][$entity_type_id][$field_name] = $values['type'];
  }

  $values['field_suggestion_' . $values['type']] = $values['field_value'];

  unset($values['field_value']);

  \Drupal::entityTypeManager()->getStorage('field_suggestion')
    ->create($values)
    ->save();

  if ($sandbox['processed'] < $sandbox['total']) {
    $sandbox['#finished'] = $sandbox['processed'] / $sandbox['total'];
  }
  else {
    \Drupal::state()->delete('field_suggestion');
  }
}
