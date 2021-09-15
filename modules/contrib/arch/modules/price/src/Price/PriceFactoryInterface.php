<?php

namespace Drupal\arch_price\Price;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Price factory interface.
 *
 * @package Drupal\arch_price
 */
interface PriceFactoryInterface extends ContainerInjectionInterface {

  /**
   * Get price instance.
   *
   * @param array $values
   *   Price values.
   *
   * @return \Drupal\arch_price\Price\PriceInterface
   *   Price instance.
   */
  public function getInstance(array $values);

  /**
   * Get ModifiedPrice instance.
   *
   * @param array $values
   *   Modified price values.
   * @param \Drupal\arch_price\Price\PriceInterface $original_price
   *   Original price instance.
   *
   * @return \Drupal\arch_price\Price\ModifiedPriceInterface
   *   Modified price instance.
   */
  public function getModifiedPriceInstance(array $values, PriceInterface $original_price);

  /**
   * Get a MissingPrice instance.
   *
   * @return \Drupal\arch_price\Price\MissingPriceInterface
   *   Missing price instance.
   */
  public function getMissingPriceInstance();

}
