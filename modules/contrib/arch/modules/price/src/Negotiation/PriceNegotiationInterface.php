<?php

namespace Drupal\arch_price\Negotiation;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Price negotiation interface.
 *
 * @package Drupal\arch_price\Negotiation
 */
interface PriceNegotiationInterface {

  /**
   * Get list of prices.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product.
   *
   * @return \Drupal\arch_price\Plugin\Field\FieldType\PriceItem[]
   *   List of prices.
   */
  public function getProductPrices(ProductInterface $product);

  /**
   * Get list of prices available for given user.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   Current user.
   *
   * @return \Drupal\arch_price\Plugin\Field\FieldType\PriceItem[]
   *   List of available prices.
   */
  public function getAvailablePrices(ProductInterface $product, AccountInterface $account = NULL);

  /**
   * Get active price.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User.
   *
   * @return \Drupal\arch_price\Price\PriceInterface
   *   Active price.
   */
  public function getActivePrice(ProductInterface $product, AccountInterface $account = NULL);

}
