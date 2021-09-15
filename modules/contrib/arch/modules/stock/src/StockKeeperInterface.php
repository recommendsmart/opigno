<?php

namespace Drupal\arch_stock;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Stock keeper interface.
 *
 * @package Drupal\arch_stock
 */
interface StockKeeperInterface extends ContainerInjectionInterface {

  /**
   * Check if stock management is enabled for type of product.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   *
   * @return bool
   *   Returns TRUE if stock management is enabled for type of product.
   */
  public function isProductManagingStock(ProductInterface $product);

  /**
   * Reduce stock of product with given amount.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   * @param float $amount
   *   Sold amount.
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Customer.
   *
   * @return bool
   *   Result.
   */
  public function reduceStock(
    ProductInterface $product,
    $amount,
    OrderInterface $order,
    AccountInterface $account
  );

  /**
   * Check if given account can access any warehouse which allow overbooking.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product instance.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account.
   *
   * @return bool
   *   Return TRUE if given user can access warehouse which allow overbooking.
   */
  public function isNegativeStockAllowed(
    ProductInterface $product,
    AccountInterface $account
  );

  /**
   * Check product has enough stock to sell for given customer.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Customer.
   *
   * @return float
   *   Total stock.
   */
  public function getTotalProductStock(
    ProductInterface $product,
    AccountInterface $account
  );

  /**
   * Check product has enough stock to sell for given customer.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Customer.
   * @param int|float $amount
   *   Required amount.
   *
   * @return bool
   *   Returns TRUE if product has enough stock.
   */
  public function hasProductEnoughStock(
    ProductInterface $product,
    AccountInterface $account,
    $amount = 1
  );

  /**
   * Get list of IDs of available warehouses.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Customer.
   *
   * @return string[]
   *   List of selected warehouse.
   */
  public function selectWarehouses(AccountInterface $account);

}
