<?php

namespace Drupal\arch_order\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Order line item interface.
 *
 * @package Drupal\arch_order\Plugin\Field\FieldType
 */
interface OrderLineItemInterface extends FieldItemInterface {

  // @todo add public function getDiscount();
  // @todo add public function getShipping();
  const ORDER_LINE_ITEM_TYPE_PRODUCT = 1;
  const ORDER_LINE_ITEM_TYPE_DISCOUNT = 100;
  const ORDER_LINE_ITEM_TYPE_SHIPPING = 200;
  const ORDER_LINE_ITEM_TYPE_PAYMENT_FEE = 255;

  /**
   * Get ID of line item type.
   *
   * @return int
   *   Line item type ID.
   */
  public function getLineItemTypeId();

  /**
   * Check if this line item is a product one.
   *
   * @return bool
   *   Return TRUE if this is a product line item.
   */
  public function isProduct();

  /**
   * Check if this line item is a discount one.
   *
   * @return bool
   *   Return TRUE if this is a discount line item.
   */
  public function isDiscount();

  /**
   * Check if this line item is a shipping one.
   *
   * @return bool
   *   Return TRUE if this is a shipping line item.
   */
  public function isShipping();

  /**
   * Check if this line item is a payment fee one.
   *
   * @return bool
   *   Return TRUE if this is a payment fee line item.
   */
  public function isPaymentFee();

  /**
   * Get product.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface
   *   Product.
   */
  public function getProduct();

  /**
   * Get quantity of line item.
   *
   * @return float|null
   *   Amount of line item.
   */
  public function getQuantity();

  /**
   * Set line item quantity.
   *
   * @param float|null $quantity
   *   New quantity.
   *
   * @return $this
   */
  public function setQuantity($quantity);

}
