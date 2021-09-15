<?php

namespace Drupal\arch_price\Manager;

use Drupal\Core\Session\AccountInterface;

/**
 * Price type Manager interface.
 *
 * @package Drupal\arch_price\Manager
 */
interface PriceTypeManagerInterface {

  /**
   * Get default price type.
   *
   * @return \Drupal\arch_price\Entity\PriceTypeInterface
   *   Default price type.
   */
  public function getDefaultPriceType();

  /**
   * List of defined price types.
   *
   * @return \Drupal\arch_price\Entity\PriceTypeInterface[]
   *   Price type list.
   */
  public function getPriceTypes();

  /**
   * Get list of types for price widget.
   *
   * @return array
   *   List of types.
   */
  public function getTypeListForWidget();

  /**
   * Get list of types available for given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account.
   * @param string $operation
   *   Operation.
   *
   * @return \Drupal\arch_price\Entity\PriceTypeInterface[]
   *   Price type list.
   */
  public function getAvailablePriceTypes(AccountInterface $account, $operation = 'view');

}
