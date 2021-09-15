<?php

namespace Drupal\arch_order\Access;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Order access control handler interface.
 *
 * @package Drupal\arch_order\Access
 */
interface OrderAccessControlHandlerInterface {

  /**
   * Gets the list of order access grants.
   *
   * This function is called to check the access grants for a order. It
   * collects all order access grants for the order from
   * hook_order_access_records() implementations, allows these grants to be
   * altered via hook_order_access_records_alter() implementations, and
   * returns the grants to the caller.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   The $order to acquire grants for.
   *
   * @return array
   *   The access rules for the order.
   */
  public function acquireGrants(OrderInterface $order);

  /**
   * Creates the default order access grant entry on the grant storage.
   */
  public function writeDefaultGrant();

  /**
   * Counts available order grants.
   *
   * @return int
   *   Returns the amount of order grants.
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
