<?php

namespace Drupal\arch_stock;

use Drupal\arch_product\Entity\ProductTypeInterface;

/**
 * Stock info interface.
 *
 * @package Drupal\arch_stock
 */
interface StockInfoInterface {

  /**
   * Check if there is any product with stock data with given type.
   *
   * @param \Drupal\arch_product\Entity\ProductTypeInterface $product_type
   *   Product type.
   *
   * @return bool
   *   Return TRUE if any.
   */
  public function typeHasStockData(ProductTypeInterface $product_type);

}
