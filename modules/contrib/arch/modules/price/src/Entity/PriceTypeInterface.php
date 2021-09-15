<?php

namespace Drupal\arch_price\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a price type entity.
 */
interface PriceTypeInterface extends ConfigEntityInterface {

  /**
   * Returns the price type name.
   *
   * @return string
   *   The price type name.
   */
  public function getName();

  /**
   * Returns the price type description.
   *
   * @return string
   *   The price type description.
   */
  public function getDescription();

  /**
   * Get default currency.
   *
   * @return string
   *   Default currency code.
   */
  public function getDefaultCurrency();

  /**
   * Get default vat category.
   *
   * @return string
   *   Default VAT category ID.
   */
  public function getDefaultVatCategory();

  /**
   * Get default calculation base value.
   *
   * @return string
   *   Get default calculation base value.
   */
  public function getDefaultCalculationBase();

  /**
   * Determines if this price type is locked.
   *
   * @return bool
   *   TRUE if the type is locked, FALSE otherwise.
   */
  public function isLocked();

}
