<?php

namespace Drupal\arch_price\Price;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Missing price interface.
 *
 * @package Drupal\arch_price
 */
interface MissingPriceInterface {

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

}
