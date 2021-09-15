<?php

namespace Drupal\arch_cart\Cart;

use Drupal\arch_price\Price\PriceFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Cart interface.
 *
 * @package Drupal\arch_cart\Cart
 */
interface CartInterface {

  const PRICE_TYPE_NET = 'net';
  const PRICE_TYPE_GROSS = 'gross';

  const ITEM_NEW = 'new';
  const ITEM_UPDATE = 'update';
  const ITEM_REMOVE = 'remove';

  /**
   * Set values.
   *
   * @param array $values
   *   Raw values.
   *
   * @return $this
   */
  public function setValues(array $values);

  /**
   * Get values.
   *
   * @return array
   *   Raw values.
   */
  public function getValues();

  /**
   * Get total price.
   *
   * @param \Drupal\currency\Entity\CurrencyInterface|string $currency
   *   Currency.
   *
   * @return array
   *   Price values.
   */
  public function getTotal($currency = NULL);

  /**
   * Get grand total price.
   *
   * @param \Drupal\currency\Entity\CurrencyInterface|string $currency
   *   Currency.
   *
   * @return array
   *   Price values.
   */
  public function getGrandTotal($currency = NULL);

  /**
   * Get total price as PriceInterface instance.
   *
   * @param \Drupal\currency\Entity\CurrencyInterface|string $currency
   *   Currency.
   *
   * @return \Drupal\arch_price\Price\PriceInterface
   *   Price instance.
   */
  public function getTotalPrice($currency = NULL);

  /**
   * Get grand total price as PriceInterface instance.
   *
   * @param \Drupal\currency\Entity\CurrencyInterface|string $currency
   *   Currency.
   *
   * @return \Drupal\arch_price\Price\PriceInterface
   *   Price instance.
   */
  public function getGrandTotalPrice($currency = NULL);

  /**
   * Add message.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   Message.
   * @param bool $merge
   *   Merge messages.
   *
   * @return $this
   */
  public function addMessage($message, $merge = TRUE);

  /**
   * Get messages.
   *
   * @return array
   *   Message list.
   */
  public function getMessages();

  /**
   * List of messages to display. Message list will be cleared.
   *
   * @param bool $clear
   *   Clear messages.
   *
   * @return string[]
   *   List of messages.
   */
  public function displayMessages($clear = TRUE);

  /**
   * Add item.
   *
   * @param mixed $item
   *   Cart item.
   *
   * @return $this
   */
  public function addItem($item);

  /**
   * Get items.
   *
   * @return array
   *   Every item.
   */
  public function getItems();

  /**
   * Check if cart has any item.
   *
   * @return bool
   *   Returns TRUE if cart has any items.
   */
  public function hasItem();

  /**
   * Get product items.
   *
   * @return array
   *   All product items.
   */
  public function getProducts();

  /**
   * Check if cart has any product.
   *
   * @return bool
   *   Returns TRUE if cart has products.
   */
  public function hasProduct();

  /**
   * Get count of products.
   *
   * @return int
   *   Item count.
   */
  public function getCount();

  /**
   * Get item by index.
   *
   * @param int $index
   *   Index.
   *
   * @return array|null
   *   Item with given index.
   */
  public function getItem($index);

  /**
   * Get item by type and ID.
   *
   * @param string $type
   *   Line item type.
   * @param string|int $id
   *   Item ID.
   *
   * @return array|null
   *   Item with given type and ID.
   */
  public function getItemById($type, $id);

  /**
   * Update item.
   *
   * @param int $index
   *   Item index.
   * @param mixed $item
   *   New values for item.
   *
   * @return $this
   */
  public function updateItem($index, $item);

  /**
   * Update item by type and ID.
   *
   * @param string $type
   *   Line item type.
   * @param string|int $id
   *   Item ID.
   * @param mixed $item
   *   New values for item.
   *
   * @return $this
   */
  public function updateItemById($type, $id, $item);

  /**
   * Update item quantity.
   *
   * @param int $index
   *   Item index.
   * @param float $quantity
   *   New item quantity.
   *
   * @return $this
   */
  public function updateItemQuantity($index, $quantity);

  /**
   * Update item quantity by type and ID.
   *
   * @param string $type
   *   Line item type.
   * @param string|int $id
   *   Item ID.
   * @param float $quantity
   *   New item quantity.
   *
   * @return $this
   */
  public function updateItemQuantityById($type, $id, $quantity);

  /**
   * Remove item.
   *
   * @param int $index
   *   Item index.
   *
   * @return $this
   */
  public function removeItem($index);

  /**
   * Remove item by type and ID.
   *
   * @param string $type
   *   Line item type.
   * @param string|int $id
   *   Item ID.
   *
   * @return $this
   */
  public function removeItemById($type, $id);

  /**
   * Get order instance for current cart.
   *
   * @return \Drupal\arch_order\Entity\OrderInterface
   *   Order instance.
   */
  public function &getOrder();

  /**
   * Place order.
   *
   * @return \Drupal\arch_order\Entity\OrderInterface
   *   New order entity.
   */
  public function placeOrder();

  /**
   * Reset cart.
   *
   * @return $this
   */
  public function resetStore();

  /**
   * Set module handler.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   *
   * @return $this
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler);

  /**
   * Get module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   Module handler instance.
   */
  public function getModuleHandler();

  /**
   * Set price factory.
   *
   * @param \Drupal\arch_price\Price\PriceFactoryInterface $price_factory
   *   Price factory.
   *
   * @return $this
   */
  public function setPriceFactory(PriceFactoryInterface $price_factory);

  /**
   * Get price factory.
   *
   * @return \Drupal\arch_price\Price\PriceFactoryInterface
   *   Price factory instance.
   */
  public function getPriceFactory();

  /**
   * Get shipping price.
   *
   * @param bool $force_update
   *   Force update.
   *
   * @return \Drupal\arch_price\Price\PriceInterface
   *   Shipping price.
   */
  public function getShippingPrice($force_update = FALSE);

  /**
   * Get payment fee.
   *
   * @param bool $force_update
   *   Force update.
   *
   * @return \Drupal\arch_price\Price\PriceInterface|null
   *   Payment fee.
   */
  public function getPaymentFee($force_update = FALSE);

  /**
   * Set default price values.
   *
   * @param array $default_price_values
   *   Default price values.
   *
   * @return $this
   */
  public function setDefaultPriceValues(array $default_price_values);

  /**
   * Set total base values.
   *
   * @param array $total_base_values
   *   Total base values.
   *
   * @return $this
   */
  public function setTotalBaseValues(array $total_base_values);

}
