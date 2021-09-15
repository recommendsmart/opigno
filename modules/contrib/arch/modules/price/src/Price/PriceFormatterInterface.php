<?php

namespace Drupal\arch_price\Price;

use Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterInterface;

/**
 * Price formatter interface.
 *
 * @package Drupal\arch_price\Price
 */
interface PriceFormatterInterface {

  const FORMAT_NET = 'net';
  const FORMAT_GROSS = 'gross';
  const FORMAT_FULL = 'full';
  const FORMAT_VAT_VALUE = 'vat';

  /**
   * Sets amount formatter.
   *
   * @param \Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterInterface $amount_formatter
   *   Amount formatter.
   *
   * @return $this
   */
  public function setAmountFormatter(AmountFormatterInterface $amount_formatter);

  /**
   * Build render array for mode.
   *
   * @param \Drupal\arch_price\Price\PriceInterface $price
   *   Price to build.
   * @param string $mode
   *   Format mode.
   * @param array $settings
   *   Format settings.
   *
   * @return array
   *   Render array.
   */
  public function buildFormatted(PriceInterface $price, $mode, array $settings = []);

  /**
   * Build formatted string.
   *
   * @param \Drupal\arch_price\Price\PriceInterface $price
   *   Price to format.
   * @param string $mode
   *   Format mode.
   * @param array $settings
   *   Format settings.
   *
   * @return string
   *   Formatted string.
   */
  public function format(PriceInterface $price, $mode, array $settings = []);

  /**
   * Build formatted net price.
   *
   * @param \Drupal\arch_price\Price\PriceInterface $price
   *   Price to build.
   * @param array $settings
   *   Format settings.
   *
   * @return array
   *   Render array.
   */
  public function buildNet(PriceInterface $price, array $settings = []);

  /**
   * Build formatted net price.
   *
   * @param \Drupal\arch_price\Price\PriceInterface $price
   *   Price to format.
   * @param array $settings
   *   Format settings.
   *
   * @return string
   *   Formatted string.
   */
  public function formatNet(PriceInterface $price, array $settings = []);

  /**
   * Build formatted gross.
   *
   * @param \Drupal\arch_price\Price\PriceInterface $price
   *   Price to build.
   * @param array $settings
   *   Format settings.
   *
   * @return array
   *   Render array.
   */
  public function buildGross(PriceInterface $price, array $settings = []);

  /**
   * Build formatted gross price.
   *
   * @param \Drupal\arch_price\Price\PriceInterface $price
   *   Price to format.
   * @param array $settings
   *   Format settings.
   *
   * @return string
   *   Formatted string.
   */
  public function formatGross(PriceInterface $price, array $settings = []);

  /**
   * Build formatted full price.
   *
   * @param \Drupal\arch_price\Price\PriceInterface $price
   *   Price to build.
   * @param array $settings
   *   Format settings.
   *
   * @return array
   *   Render array.
   */
  public function buildFull(PriceInterface $price, array $settings = []);

  /**
   * Build formatted full price.
   *
   * @param \Drupal\arch_price\Price\PriceInterface $price
   *   Price to format.
   * @param array $settings
   *   Format settings.
   *
   * @return string
   *   Formatted string.
   */
  public function formatFull(PriceInterface $price, array $settings = []);

  /**
   * Build formatted VAT value.
   *
   * @param \Drupal\arch_price\Price\PriceInterface $price
   *   Price to build.
   * @param array $settings
   *   Format settings.
   *
   * @return array
   *   Render array.
   */
  public function buildVat(PriceInterface $price, array $settings = []);

  /**
   * Build formatted VAT value.
   *
   * @param \Drupal\arch_price\Price\PriceInterface $price
   *   Price to format.
   * @param array $settings
   *   Format settings.
   *
   * @return string
   *   Formatted string.
   */
  public function formatVat(PriceInterface $price, array $settings = []);

}
