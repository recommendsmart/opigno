<?php

namespace Drupal\arch_price\Manager;

/**
 * VAT category manager interface.
 *
 * @package Drupal\arch_price\Manager
 */
interface VatCategoryManagerInterface {

  /**
   * Get default VAT category.
   *
   * @return \Drupal\arch_price\Entity\VatCategoryInterface
   *   Default VAT category.
   */
  public function getDefaultVatCategory();

  /**
   * List of defined VAT categories.
   *
   * @return \Drupal\arch_price\Entity\VatCategoryInterface[]
   *   VAT category list.
   */
  public function getVatCategories();

  /**
   * Get list of VAT categories for price widget.
   *
   * @return array
   *   VAT categories.
   */
  public function getVatCategoryListForWidget();

  /**
   * Get VAT category.
   *
   * @param string $id
   *   VAT category ID.
   *
   * @return null|\Drupal\arch_price\Entity\VatCategoryInterface
   *   VAT category or NULL.
   */
  public function getVatCategory($id);

  /**
   * Get rate for VAT category.
   *
   * @param string $id
   *   VAT category ID.
   *
   * @return float
   *   Rate.
   */
  public function getVatRate($id);

  /**
   * Get percent rate for VAT category.
   *
   * @param string $id
   *   VAT category ID.
   *
   * @return float
   *   Rate percent.
   */
  public function getVatRatePercent($id);

}
