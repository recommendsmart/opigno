<?php

namespace Drupal\arch_stock\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Defines a storage handler class for warehouses.
 */
class WarehouseStorage extends ConfigEntityStorage implements WarehouseStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('arch_stock_warehouse_get_names');
    parent::resetCache($ids);
  }

}
