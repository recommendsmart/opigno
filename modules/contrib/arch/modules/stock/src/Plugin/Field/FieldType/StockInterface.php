<?php

namespace Drupal\arch_stock\Plugin\Field\FieldType;

/**
 * Stock interface.
 *
 * @package Drupal\arch_stock\Plugin\Field\FieldType
 */
interface StockInterface {

  /**
   * Get warehouse ID.
   *
   * @return string
   *   Warehouse ID.
   */
  public function getWarehouseId();

  /**
   * Get warehouse entity.
   *
   * @return \Drupal\arch_stock\Entity\WarehouseInterface
   *   Warehouse.
   */
  public function getWarehouse();

  /**
   * Get quantity.
   *
   * @return float
   *   Quantity.
   */
  public function getQuantity();

  /**
   * Get cart quantity.
   *
   * @return float
   *   Quantity in carts.
   */
  public function getCartQuantity();

  /**
   * Check currently this item has items in stock.
   *
   * @return bool
   *   Returns TRUE if currently available.
   */
  public function isAvailable();

}
