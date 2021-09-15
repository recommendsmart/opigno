<?php

namespace Drupal\arch_price\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Defines a storage handler class for VAT categories.
 */
class VatCategoryStorage extends ConfigEntityStorage implements VatCategoryStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL) {
    drupal_static_reset('arch_price_vat_category_get_names');
    parent::resetCache($ids);
  }

}
