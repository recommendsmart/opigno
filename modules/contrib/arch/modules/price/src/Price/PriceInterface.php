<?php

namespace Drupal\arch_price\Price;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Price interface.
 *
 * @package Drupal\arch_price
 */
interface PriceInterface {

  const FORMAT_NET = 'net';
  const FORMAT_GROSS = 'gross';
  const FORMAT_FULL = 'full';
  const FORMAT_VAT_VALUE = 'vat';

  const MODE_NET = 'net';
  const MODE_GROSS = 'gross';
  const MODE_NET_GROSS = 'net_gross';
  const MODE_GROSS_NET = 'gross_net';

  /**
   * Price values.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Service container.
   * @param array $values
   *   Price values.
   *
   * @return static
   *   Price instance.
   */
  public static function create(
    ContainerInterface $container,
    array $values
  );

  /**
   * Set price values.
   *
   * @param array $values
   *   Price values.
   *
   * @return $this
   */
  public function setValues(array $values);

  /**
   * Get price values as array.
   *
   * @return array
   *   Price values.
   */
  public function getValues();

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
   * Get VAT value.
   *
   * @return float
   *   VAT value.
   */
  public function getVatValue();

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
   * Get currency ID.
   *
   * @return string
   *   Currency ID.
   */
  public function getCurrencyId();

  /**
   * Get currency.
   *
   * @return \Drupal\currency\Entity\CurrencyInterface
   *   Currency.
   */
  public function getCurrency();

  /**
   * Get price type entity.
   *
   * @return \Drupal\arch_price\Entity\PriceTypeInterface|null
   *   Price type entity or NULL on failure.
   */
  public function getPriceType();

  /**
   * Get price type ID.
   *
   * @return string|null
   *   Price type id.
   */
  public function getPriceTypeId();

  /**
   * Get calculation base price.
   *
   * @return string
   *   Base field.
   */
  public function getCalculationBase();

  /**
   * Get calculation base price.
   *
   * @return string
   *   Base field.
   */
  public function getVatCategoryId();

  /**
   * Get exchanged values for given currency.
   *
   * @param \Drupal\currency\Entity\CurrencyInterface|string $currency
   *   Currency entity.
   *
   * @return array|null
   *   Price values or NULL on failure.
   */
  public function getExchangedPriceValues($currency);

  /**
   * Get exchanged price for given currency.
   *
   * @param \Drupal\currency\Entity\CurrencyInterface|string $currency
   *   Currency entity.
   *
   * @return \Drupal\arch_price\Price\PriceInterface|null
   *   Price instance or NULL on failure.
   */
  public function getExchangedPrice($currency);

  /**
   * Set the reason of difference.
   *
   * @param string $reason
   *   The reason of difference.
   *
   * @return $this
   */
  public function setReasonOfDifference($reason);

  /**
   * Get the reason of difference.
   *
   * @return string
   *   Reason of difference.
   */
  public function getReasonOfDifference();

}
