<?php

namespace Drupal\arch_stock;

/**
 * Stock cart info interface.
 *
 * @package Drupal\arch_stock
 */
interface StockCartInfoInterface {

  /**
   * Store new cart item.
   *
   * @param int $product_id
   *   Product ID.
   * @param float $quantity
   *   New quantity.
   *
   * @return $this
   */
  public function addItem($product_id, $quantity);

  /**
   * Remove cart item.
   *
   * @param int $product_id
   *   Product ID.
   * @param float $quantity
   *   Removed quantity.
   *
   * @return $this
   */
  public function removeItem($product_id, $quantity);

  /**
   * Update item quantity.
   *
   * @param int $product_id
   *   Product ID.
   * @param float $quantity
   *   New item count.
   *
   * @return $this
   */
  public function updateItem($product_id, $quantity);

  /**
   * Get total amount of products in carts.
   *
   * @param int $product_id
   *   Product ID.
   *
   * @return float
   *   Current total quantity in carts.
   */
  public function quantityInCarts($product_id);

  /**
   * Cleanup.
   */
  public function garbageCollection();

}
