<?php

namespace Drupal\arch_price\Price;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Modified price interface.
 *
 * @package Drupal\arch_price
 */
interface ModifiedPriceInterface extends PriceInterface {

  /**
   * Price values.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Service container.
   * @param array $values
   *   Price values.
   *
   * @return \Drupal\arch_price\Price\ModifiedPriceInterface
   *   Price instance.
   */
  public static function create(
    ContainerInterface $container,
    array $values
  );

  /**
   * Set origin price.
   *
   * @param \Drupal\arch_price\Price\PriceInterface $original_price
   *   Original price.
   *
   * @return $this
   */
  public function setOriginalPrice(PriceInterface $original_price);

  /**
   * Get original price.
   *
   * @return \Drupal\arch_price\Price\PriceInterface
   *   Original price instance.
   */
  public function getOriginalPrice();

}
