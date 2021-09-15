<?php

namespace Drupal\arch_price\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Defines a storage handler class for price types.
 */
class PriceTypeStorage extends ConfigEntityStorage implements PriceTypeStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('arch_price_price_type_get_names');
    parent::resetCache($ids);
  }

}
