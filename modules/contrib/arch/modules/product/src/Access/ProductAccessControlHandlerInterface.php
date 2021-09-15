<?php

namespace Drupal\arch_product\Access;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Product access control handler interface.
 *
 * @package Drupal\arch_product\Access
 */
interface ProductAccessControlHandlerInterface {

  /**
   * Gets the list of product access grants.
   *
   * This function is called to check the access grants for a product. It
   * collects all product access grants for the product from
   * hook_product_access_records() implementations, allows these grants to be
   * altered via hook_product_access_records_alter() implementations, and
   * returns the grants to the caller.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   The $product to acquire grants for.
   *
   * @return array
   *   The access rules for the product.
   */
  public function acquireGrants(ProductInterface $product);

  /**
   * Creates the default product access grant entry on the grant storage.
   */
  public function writeDefaultGrant();

  /**
   * Deletes all product access entries.
   */
  public function deleteGrants();

  /**
   * Counts available product grants.
   *
   * @return int
   *   Returns the amount of product grants.
   */
  public function countGrants();

  /**
   * Checks all grants for a given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   *
   * @return int
   *   Status of the access check.
   */
  public function checkAllGrants(AccountInterface $account);

}
