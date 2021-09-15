<?php

namespace Drupal\arch_price\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Price item interface.
 *
 * @package Drupal\arch_price\Plugin\Field\FieldType
 */
interface PriceItemInterface extends FieldItemInterface {

  /**
   * Return value as Price instance.
   *
   * @return \Drupal\arch_price\Price\PriceInterface
   *   Price instance.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function toPrice();

  /**
   * Get price type ID.
   *
   * @return string
   *   Price type ID.
   */
  public function getPriceTypeId();

  /**
   * Get price type entity.
   *
   * @return \Drupal\arch_price\Entity\PriceTypeInterface
   *   Price type.
   */
  public function getPriceType();

  /**
   * Get currency ID.
   *
   * @return string
   *   Currency ID.
   */
  public function getCurrencyId();

  /**
   * Get currency entity.
   *
   * @return \Drupal\currency\Entity\CurrencyInterface
   *   Currency.
   */
  public function getCurrency();

  /**
   * Get calculation base price.
   *
   * @return string
   *   Base field.
   */
  public function getCalculationBase();

  /**
   * Get net price.
   *
   * @return float
   *   Net price.
   */
  public function getNetPrice();

  /**
   * Get gross price.
   *
   * @return float
   *   Get gross price.
   */
  public function getGrossPrice();

  /**
   * Get VAT category ID.
   *
   * @return string
   *   VAT category ID.
   */
  public function getVatCategoryId();

  /**
   * Get VAT category.
   *
   * @return \Drupal\arch_price\Entity\VatCategoryInterface
   *   VAT category.
   */
  public function getVatCategory();

  /**
   * Get VAT rate.
   *
   * @return float
   *   VAT rate.
   */
  public function getVatRate();

  /**
   * Get VAT rate percentage.
   *
   * @return float
   *   VAT rate percentage.
   */
  public function getVatRatePercentage();

  /**
   * Get VAT value.
   *
   * @return float
   *   VAT value.
   */
  public function getVatValue();

  /**
   * Get available from.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   Available from.
   */
  public function getAvailableFrom();

  /**
   * Get available to.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   Available to.
   */
  public function getAvailableTo();

  /**
   * Check currently this price is available.
   *
   * @return bool
   *   Returns TRUE if currently available.
   */
  public function isAvailable();

  /**
   * Check price is available at the given time.
   *
   * @param \Drupal\Component\Datetime\DateTimePlus|\DateTime $time
   *   DateTime to check.
   *
   * @return bool
   *   Returns TRUE if available at the given time.
   */
  public function isAvailableAt($time);

}
