<?php

namespace Drupal\arch_order\Entity;

use Drupal\arch_cart\Cart\CartInterface;
use Drupal\arch_order\Services\OrderAddressServiceInterface;
use Drupal\arch_payment\PaymentMethodInterface;
use Drupal\arch_price\Price\PriceInterface;
use Drupal\arch_shipping\ShippingMethodInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\UserInterface;

/**
 * Order entity interface.
 *
 * @package Drupal\arch_order\Entity
 */
interface OrderInterface extends ContentEntityInterface, RevisionLogInterface {

  /**
   * Create new order from cart instance.
   *
   * @param \Drupal\arch_cart\Cart\CartInterface $cart
   *   Cart instance.
   *
   * @return \Drupal\arch_order\Entity\OrderInterface
   *   New order entity.
   */
  public static function createFromCart(CartInterface $cart);

  /**
   * Sets the owner of the Order entity.
   *
   * @param \Drupal\user\UserInterface $user
   *   Loaded user.
   *
   * @return \Drupal\arch_order\Entity\Order
   *   Actual Order object.
   */
  public function setOwner(UserInterface $user);

  /**
   * Gets the actual owner of Order entity.
   *
   * @return \Drupal\user\Entity\User|null
   *   Returns the actual owner of Order entity, or NULL if not set.
   */
  public function getOwner();

  /**
   * Sets owner id.
   *
   * @param string|int $ownerId
   *   Owner id.
   *
   * @return $this
   *   Order entity instance.
   */
  public function setOwnerId($ownerId);

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId();

  /**
   * Gets owner id.
   *
   * @return string|int
   *   Owner id.
   */
  public function getOwnerId();

  /**
   * Sets the product creation timestamp.
   *
   * @param int $timestamp
   *   The product creation timestamp.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface
   *   The called product entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the product creation timestamp.
   *
   * @return int
   *   Creation timestamp of the product.
   */
  public function getCreatedTime();

  /**
   * Sets the product changed timestamp.
   *
   * @param int $timestamp
   *   The product changed timestamp.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface
   *   The called product entity.
   */
  public function setChangedTime($timestamp);

  /**
   * Gets the product changed timestamp.
   *
   * @return int
   *   Changed timestamp of the product.
   */
  public function getChangedTime();

  /**
   * Gets the count of purchased products.
   *
   * @return int
   *   Count of purchased product.
   */
  public function getLineItemsCount();

  /**
   * Gets the count of purchased products. This is an alias.
   *
   * @return int
   *   Count of purchased product.
   */
  public function getProductsCount();

  /**
   * Get list of filtered line items.
   *
   * @param callable|array|string $callback
   *   The callback to use for filtering. Like with array_filter(), the
   *   callback is called for each item in the list. Only items for which the
   *   callback returns TRUE are return.
   *
   * @return \Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemFieldItem[]
   *   Result.
   */
  public function filterLineItems($callback);

  /**
   * Gets the list of purchased products.
   *
   * @return \Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemFieldItem[]
   *   Product line item list.
   */
  public function getProducts();

  /**
   * Gets the list of shipping price line items.
   *
   * @return \Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemFieldItem[]
   *   Shipping price line item list.
   */
  public function getShippingPrices();

  /**
   * Gets the list of discount line items.
   *
   * @return \Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemFieldItem[]
   *   Discount line item list.
   */
  public function getDiscounts();

  /**
   * Get serialized data of order.
   *
   * @return array
   *   Data array.
   */
  public function getData();

  /**
   * Update order data.
   *
   * @param array $data
   *   New data.
   *
   * @return $this
   */
  public function setData(array $data);

  /**
   * Get data key.
   *
   * @param string $key
   *   Data key.
   * @param mixed|null $default
   *   Default value.
   *
   * @return mixed
   *   Data value.
   */
  public function getDataKey($key, $default = NULL);

  /**
   * Update data value.
   *
   * @param string $key
   *   Data key.
   * @param mixed $value
   *   Data value.
   *
   * @return $this
   */
  public function setDataKey($key, $value);

  /**
   * Sets selected shipping method for the order.
   *
   * @param \Drupal\arch_shipping\ShippingMethodInterface $shipping_method
   *   Shipping method.
   *
   * @return $this
   */
  public function setShippingMethod(ShippingMethodInterface $shipping_method);

  /**
   * Gets selected shipping method.
   *
   * @return \Drupal\arch_shipping\ShippingMethodInterface
   *   The selected shipping method for the order.
   */
  public function getShippingMethod();

  /**
   * Sets selected shipping method for the order.
   *
   * @param \Drupal\arch_payment\PaymentMethodInterface $payment_method
   *   Payment method.
   *
   * @return $this
   */
  public function setPaymentMethod(PaymentMethodInterface $payment_method);

  /**
   * Gets selected payment method.
   *
   * @return \Drupal\arch_payment\PaymentMethodInterface
   *   The selected payment method for the order.
   */
  public function getPaymentMethod();

  /**
   * Set order address service.
   *
   * @param \Drupal\arch_order\Services\OrderAddressServiceInterface $order_address_service
   *   Order address service.
   *
   * @return $this
   */
  public function setOrderAddressService(OrderAddressServiceInterface $order_address_service);

  /**
   * Get order address service.
   *
   * @return \Drupal\arch_order\Services\OrderAddressServiceInterface
   *   Order address service.
   */
  public function getOrderAddressService();

  /**
   * Set billing address.
   *
   * @param \Drupal\arch_order\OrderAddressDataInterface|null $address
   *   Billing address.
   *
   * @return $this
   */
  public function setBillingAddress($address = NULL);

  /**
   * Get current billing address data.
   *
   * @return \Drupal\arch_order\OrderAddressDataInterface|null
   *   Billing address data.
   */
  public function getBillingAddress();

  /**
   * Set shipping address.
   *
   * @param \Drupal\arch_order\OrderAddressDataInterface|null $address
   *   Shipping address.
   *
   * @return $this
   */
  public function setShippingAddress($address = NULL);

  /**
   * Get shipping address.
   *
   * @return \Drupal\arch_order\OrderAddressDataInterface|null
   *   Shipping address data.
   */
  public function getShippingAddress();

  /**
   * Add shipping price line item built from given price.
   *
   * @param \Drupal\arch_price\Price\PriceInterface $price
   *   Price value.
   * @param string $method_id
   *   Shipping method plugin id.
   *
   * @return $this
   */
  public function setShippingPrice(PriceInterface $price, $method_id);

  /**
   * Add payment fee line item.
   *
   * @param \Drupal\arch_price\Price\PriceInterface $price
   *   Price value.
   * @param string $method_id
   *   Payment method plugin id.
   *
   * @return $this
   */
  public function setPaymentFee(PriceInterface $price, $method_id);

  /**
   * Get order status entity.
   *
   * @return \Drupal\arch_order\Entity\OrderStatusInterface
   *   Order status entity.
   */
  public function getStatus();

  /**
   * Order status ID.
   *
   * @return string
   *   Order status ID.
   */
  public function getStatusId();

}
