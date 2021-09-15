<?php

namespace Drupal\arch_price\Price;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Missing price.
 *
 * @package Drupal\arch_price\Price
 */
class MissingPrice extends Price implements MissingPriceInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $values = NULL
  ) {
    return new static(
      $container,
      $values
    );
  }

  /**
   * MissingPrice constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Service container.
   * @param array|null $values
   *   Price values.
   */
  public function __construct(
    ContainerInterface $container,
    array $values = NULL
  ) {
    parent::__construct($container, [
      'base' => 'net',
      'price_type' => NULL,
      'currency' => 'XXX',
      'net' => 0,
      'gross' => 0,
      'vat_category' => 'default',
      'vat_rate' => 0,
      'vat_value' => NULL,
      'date_from' => NULL,
      'date_to' => NULL,
      'reason_of_diff' => NULL,
    ]);
  }

}
