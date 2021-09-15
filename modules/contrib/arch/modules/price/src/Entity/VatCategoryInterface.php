<?php

namespace Drupal\arch_price\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a VAT category entity.
 */
interface VatCategoryInterface extends ConfigEntityInterface {

  /**
   * Returns the VAT category name.
   *
   * @return string
   *   The VAT category name.
   */
  public function getName();

  /**
   * Returns the VAT category description.
   *
   * @return string
   *   The VAT category description.
   */
  public function getDescription();

  /**
   * Returns the VAT category rate.
   *
   * @return float
   *   The VAT category rate.
   */
  public function getRate();

  /**
   * Returns the VAT rate in percent, i.e.: 27.0 for 0.27 rate.
   *
   * @return float
   *   Rate as percent.
   */
  public function getRatePercent();

  /**
   * Determines if this VAT category is locked.
   *
   * @return bool
   *   TRUE if the category is locked, FALSE otherwise.
   */
  public function isLocked();

  /**
   * Determines if this VAT category is custom.
   *
   * @return bool
   *   TRUE if the category is custom, FALSE otherwise.
   */
  public function isCustom();

}
