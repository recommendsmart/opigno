<?php

/**
 * @file
 * Post update functions for the Collection module.
 */

/**
 * Update all collection_items to update labels.
 */
function collection_post_update_collection_item_labels(&$sandbox) {
  $item_storage = \Drupal::service('entity_type.manager')->getStorage('collection_item');
  $collection_items = $item_storage->loadMultiple();

  foreach ($collection_items as $collection_item) {
    // Retain the changed time value. Note the `+ 1` hack. This is because
    // Drupal\Core\Field\Plugin\Field\FieldType\ChangedItem::preSave() has some
    // wonky logic for automating the value to the current time.
    $changed_time = $collection_item->getChangedTime();
    $collection_item->setChangedTime($changed_time + 1);

    // This should trigger CollectionItem::preSave() and update the
    // collection_item label.
    $collection_item->save();
  }
}
