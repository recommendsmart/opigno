<?php

namespace Drupal\arch_price\Price;

/**
 * Modified price.
 *
 * @package Drupal\arch_price\Price
 */
class ModifiedPrice extends Price implements ModifiedPriceInterface {

  /**
   * Original price.
   *
   * @var \Drupal\arch_price\Price\PriceInterface
   */
  protected $originalPrice;

  /**
   * {@inheritdoc}
   */
  public function setOriginalPrice(PriceInterface $original_price) {
    $this->originalPrice = $original_price;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalPrice() {
    return $this->originalPrice;
  }

}
