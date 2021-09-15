<?php

namespace Drupal\arch_product\Entity\Storage;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the product schema handler.
 *
 * @package Drupal\arch_product\Entity\Storage
 */
class ProductStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(
    ContentEntityTypeInterface $entity_type,
    $reset = FALSE
  ) {
    $schema = parent::getEntitySchema($entity_type, $reset);
    if ($data_table = $this->storage->getDataTable()) {
      if (!empty($schema[$data_table]['title'])) {
        $schema[$data_table]['indexes']['product__title_type'] = [
          'title',
          ['type', 4],
        ];
      }
      if (
        !empty($schema[$data_table]['promote'])
        && !empty($schema[$data_table]['status'])
        && !empty($schema[$data_table]['sticky'])
        && !empty($schema[$data_table]['created'])
      ) {
        $schema[$data_table]['indexes']['product__frontpage'] = [
          'promote',
          'status',
          'sticky',
          'created',
        ];
      }
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(
    FieldStorageDefinitionInterface $storage_definition,
    $table_name,
    array $column_mapping
  ) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'arch_product_revision') {
      switch ($field_name) {
        case 'langcode':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;

        case 'revision_uid':
          $this->addSharedTableFieldForeignKey($storage_definition, $schema, 'users', 'uid');
          break;
      }
    }

    if ($table_name == 'arch_product_field_data') {
      switch ($field_name) {
        case 'promote':
        case 'status':
        case 'sticky':
        case 'title':
        case 'sku':
          // Improves the performance of the indexes defined
          // in getEntitySchema().
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;

        case 'changed':
        case 'created':
          // @todo Revisit index definitions:
          //   https://www.drupal.org/node/2015277.
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;
      }
    }

    return $schema;
  }

}
