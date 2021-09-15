<?php
/**
 * @file
 * Hooks specific to the Stock module.
 */

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\arch_product\Entity\ProductInterface;
use Drupal\arch_stock\Entity\WarehouseInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Respond on stock reduction.
 *
 * @param float $sold_amount
 *   Sold amount.
 * @param \Drupal\arch_product\Entity\ProductInterface $product
 *   Modified product.
 * @param \Drupal\arch_order\Entity\OrderInterface $order
 *   Processed order.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   Customer account.
 * @param array $original_stock_values
 *   Original values of stock field of given Product instance.
 */
function hook_stock_reduced($sold_amount, ProductInterface $product, OrderInterface $order, AccountInterface $account, array $original_stock_values) {
  // @todo Add example implementation.
}

/**
 * Alter selected warehouses for stock keeper.
 *
 * @param string[] $list
 *   Warehouse ID list.
 * @param array $context
 *   Alter context with given keys:
 *   - account: \Drupal\Core\Session\AccountInterface Customer account.
 *   - warehouses: \Drupal\arch_stock\Entity\WarehouseInterface[] all available
 *   warehouse.
 */
function hook_stock_keeper_selected_warehouses_alter(array $list, array $context) {
  // @todo Add example implementation.
}

/**
 * Alter list of available warehouses.
 *
 * @param \Drupal\arch_stock\Entity\WarehouseInterface[] $warehouses
 *   Warehouse entity list.
 * @param array $context
 *   Alter context with given keys:
 *   - account: \Drupal\Core\Session\AccountInterface Customer account.
 */
function hook_stock_available_warehouses_alter(array $warehouses, array $context) {
  // @todo Add example implementation.
}

/**
 * Modify availability of negative stock.
 *
 * @param \Drupal\Core\Access\AccessResult $allow_negative
 *   Access result.
 * @param \Drupal\arch_stock\Entity\WarehouseInterface $warehouse
 *   Warehouse to check.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   Account to check.
 */
function hook_allow_negative_stock_for_warehouse_alter(AccessResult $allow_negative, WarehouseInterface $warehouse, AccountInterface $account) {
  if ($account->isAnonymous()) {
    $allow_negative->andIf(AccessResult::forbidden());
  }
}

/**
 * Modify available stock.
 *
 * @param int|float $stock_number
 *   Stock number.
 * @param \Drupal\arch_product\Entity\ProductInterface $product
 *   Product.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   User account.
 */
function hook_product_stock_alter($stock_number, ProductInterface $product, AccountInterface $account) {
  // @todo Add example implementation.
}

/**
 * @} End of "addtogroup hooks".
 */
