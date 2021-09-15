<?php

namespace Drupal\arch_stock\Manager;

use Drupal\Core\Session\AccountInterface;

/**
 * Warehouse manager interface.
 *
 * @package Drupal\arch_stock\Manager
 */
interface WarehouseManagerInterface {

  /**
   * Get default warehouse.
   *
   * @return \Drupal\arch_stock\Entity\WarehouseInterface
   *   Default warehouse.
   */
  public function getDefaultWarehouse();

  /**
   * List of defined warehouses.
   *
   * @return \Drupal\arch_stock\Entity\WarehouseInterface[]
   *   Warehouse list.
   */
  public function getWarehouses();

  /**
   * Get form options.
   *
   * @return array
   *   List of warehouses.
   */
  public function getFormOptions();

  /**
   * Get available warehouses for given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user.
   *
   * @return \Drupal\arch_stock\Entity\WarehouseInterface[]
   *   List of available warehouses.
   */
  public function getAvailableWarehouses(AccountInterface $account);

}
