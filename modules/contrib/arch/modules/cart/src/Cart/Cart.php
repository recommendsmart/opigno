<?php

namespace Drupal\arch_cart\Cart;

use Drupal\arch_order\Entity\Order;
use Drupal\arch_price\Price\PriceFactoryInterface;
use Drupal\arch_price\Price\PriceInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\currency\Entity\CurrencyInterface;

/**
 * Default cart implementation.
 *
 * @package Drupal\arch_cart\Cart
 */
class Cart implements CartInterface {

  use DependencySerializationTrait;

  /**
   * Store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $store;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Price factory.
   *
   * @var \Drupal\arch_price\Price\PriceFactoryInterface
   */
  protected $priceFactory;

  /**
   * Stored values.
   *
   * @var array
   */
  protected $values;

  /**
   * Shipping price.
   *
   * @var \Drupal\arch_price\Price\PriceInterface
   */
  protected $shippingPrice;

  /**
   * Payment fee.
   *
   * @var \Drupal\arch_price\Price\PriceInterface
   */
  protected $paymentFee;

  /**
   * Order entity (Note: a non-saved order only).
   *
   * @var \Drupal\arch_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Default price values.
   *
   * @var array
   */
  protected $defaultPrice;

  /**
   * Total base values.
   *
   * @var array
   */
  protected $totalBaseValues = [
    'base' => 'net',
    'price_type' => 'default',
    'currency' => NULL,
    'net' => 0,
    'gross' => 0,
    'vat_category' => 'custom',
    'vat_rate' => 0,
    'vat_value' => 0,
    'date_from' => NULL,
    'date_to' => NULL,
  ];

  /**
   * Price type object.
   *
   * @var \Drupal\arch_price\Entity\PriceTypeInterface
   */
  protected $priceType;

  /**
   * Cart constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStore $store
   *   Store.
   */
  public function __construct(PrivateTempStore $store) {
    $this->store = $store;
    $this->readFromStore();
    $this->order = Order::createFromCart($this);
  }

  /**
   * {@inheritdoc}
   */
  public function setValues(array $values) {
    $this->values = $values + [
      'items' => [],
      'messages' => [],
    ];

    $items = [];
    foreach ($this->values['items'] as $item) {
      $key = $item['type'] . '::' . $item['id'];
      if (!isset($items[$key])) {
        $items[$key] = $item;
        continue;
      }

      $items[$key]['quantity'] += $item['quantity'];
    }

    if (count($items) !== count($this->values['items'])) {
      $this->values['items'] = array_values($items);
      $this->updateStore();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getValues() {
    return $this->values;
  }

  /**
   * Update store.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function updateStore() {
    $this->store->set('cart', $this->values);
  }

  /**
   * Read data from store.
   *
   * @return $this
   */
  protected function readFromStore() {
    $this->setValues((array) $this->store->get('cart'));
    return $this;
  }

  /**
   * Reset cart store.
   *
   * @return $this
   *   This cart instance.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function resetStore() {
    $this->setValues([]);
    $this->store->set('cart', $this->values);

    return $this;
  }

  /**
   * Build total values from given items.
   *
   * @param array $items
   *   Item list.
   * @param \Drupal\currency\Entity\CurrencyInterface|string $currency
   *   Currency.
   *
   * @return array
   *   Price values.
   */
  protected function buildTotal(array $items, $currency = NULL) {
    $values = $this->totalBaseValues;
    /** @var \Drupal\currency\Entity\CurrencyInterface $currency */
    if (!empty($currency) && is_string($currency)) {
      $currency = $this->loadCurrency($currency);
    }

    if (!($currency instanceof CurrencyInterface)) {
      $currency = NULL;
    }
    else {
      $values['currency'] = $currency->id();
    }

    foreach ($items as $item) {
      $price = NULL;
      if (
        $item['type'] == 'product'
        && ($product = $this->loadProduct($item['id']))
      ) {
        /** @var \Drupal\arch_price\Price\PriceInterface $price */
        $price = $product->getActivePrice();
      }

      if (empty($price)) {
        continue;
      }

      if (empty($currency)) {
        $currency = $price->getCurrency();
        $values['currency'] = $price->getCurrencyId();
      }
      elseif ($price->getCurrencyId() !== $values['currency']) {
        $price = $price->getExchangedPrice($currency);
      }

      $values['net'] += $price->getNetPrice() * $item['quantity'];
      $values['gross'] += $price->getGrossPrice() * $item['quantity'];
      $values['vat_value'] += $price->getVatValue();
    }

    $default_price_values = $this->getDefaultPriceValues();
    if (!isset($values['currency'])) {
      $values['currency'] = $default_price_values['currency'];
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotal($currency = NULL) {
    return $this->buildTotal($this->getProducts(), $currency);
  }

  /**
   * {@inheritdoc}
   */
  public function getGrandTotal($currency = NULL) {
    $total = $this->buildTotal($this->getItems(), $currency);

    $shipping_price = $this->getShippingPrice();
    if ($shipping_price->getCurrencyId() !== $total['currency']) {
      $shipping_price = $shipping_price->getExchangedPrice($total['currency']);
    }
    $total['net'] += $shipping_price->getNetPrice();
    $total['gross'] += $shipping_price->getGrossPrice();
    $total['vat_value'] += $shipping_price->getVatValue();

    $payment_fee = $this->getPaymentFee();
    if (!empty($payment_fee)) {
      if ($payment_fee->getCurrencyId() !== $total['currency']) {
        $payment_fee = $payment_fee->getExchangedPrice($total['currency']);
      }
      $total['net'] += $payment_fee->getNetPrice();
      $total['gross'] += $payment_fee->getGrossPrice();
      $total['vat_value'] += $payment_fee->getVatValue();
    }

    return $total;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalPrice($currency = NULL) {
    return $this->buildPriceInstance($this->getTotal($currency));
  }

  /**
   * {@inheritdoc}
   */
  public function getGrandTotalPrice($currency = NULL) {
    return $this->buildPriceInstance($this->getGrandTotal($currency));
  }

  /**
   * Get values as PriceInterface instance.
   *
   * @param array $values
   *   Price values.
   *
   * @return \Drupal\arch_price\Price\PriceInterface
   *   Price instance.
   */
  protected function buildPriceInstance(array $values) {
    $values += [
      'base' => 'net',
      'vat_rate' => 0,
    ];
    // @todo If $values['vat_category'] == 'custom', then should we step over?
    // @todo Since different vat_categories can cause FAKE vat_rate.
    if (!empty($values['net'])) {
      $values['vat_rate'] = round(($values['gross'] / $values['net']) - 1, 4);
    }
    $values['vat_value'] = $values['gross'] - $values['net'];

    return $this->getPriceFactory()->getInstance($values);
  }

  /**
   * {@inheritdoc}
   */
  public function addMessage($message, $merge = TRUE) {
    $this->values['messages'][] = [
      'message' => $message,
      'merge' => $merge,
    ];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages() {
    return $this->values['messages'];
  }

  /**
   * {@inheritdoc}
   */
  public function displayMessages($clear = TRUE) {
    $messages = [];
    foreach ($this->values['messages'] as $msg) {
      $key = md5($msg['message']);
      if (!isset($messages[$key])) {
        $messages[$key] = (string) $msg['message'];
        continue;
      }

      if ($msg['merge']) {
        continue;
      }

      $key = Html::getUniqueId($key);
      $messages[$key] = (string) $msg['message'];
    }

    if ($clear) {
      $this->values['messages'] = [];
      $this->updateStore();
    }
    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function addItem($item) {
    $key = NULL;
    $existing_quantity = NULL;
    foreach ($this->values['items'] as $i => $value) {
      if (
        $value['type'] == $item['type']
        && $value['id'] == $item['id']
      ) {
        $key = $i;
        $existing_quantity = $value['quantity'];
        break;
      }
    }

    if (isset($key)) {
      return $this->updateItemQuantity($key, $existing_quantity + $item['quantity']);
    }

    $this->values['items'][] = $item;
    $this->onCartUpdate(self::ITEM_NEW, $item, NULL);
    $this->updateStore();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    $items = (array) $this->values['items'];
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function hasItem() {
    $items = $this->getItems();
    return !empty($items);
  }

  /**
   * {@inheritdoc}
   */
  public function getProducts() {
    return array_values(array_filter($this->getItems(), function ($item) {
      if (!empty($item['_removed'])) {
        return FALSE;
      }
      if (
        $item['type'] !== 'product'
      ) {
        return FALSE;
      }
      return !empty($item['quantity']) && $item['quantity'] > 0;
    }));
  }

  /**
   * {@inheritdoc}
   */
  public function getCount() {
    return count($this->getProducts());
  }

  /**
   * {@inheritdoc}
   */
  public function hasProduct() {
    $products = $this->getProducts();
    return !empty($products);
  }

  /**
   * {@inheritdoc}
   */
  public function getItem($index) {
    $items = $this->getItems();
    if (!isset($items[$index])) {
      return NULL;
    }
    return !empty($items[$index]['_removed']) ? NULL : $items[$index];
  }

  /**
   * {@inheritdoc}
   */
  public function getItemById($type, $id) {
    foreach ($this->getItems() as $item) {
      if ($item['type'] == $type && $item['id'] == $id) {
        return $item;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function updateItem($index, $item) {
    if (empty($this->values['items'][$index])) {
      throw new \Exception('Cannot update missing item');
    }

    if (
      empty($item['quantity'])
      || $item['quantity'] <= 0
    ) {
      return $this->removeItem($index);
    }

    $old_item = $this->values['items'][$index];
    $this->values['items'][$index] = $item;
    $this->onCartUpdate(self::ITEM_UPDATE, $item, $old_item);

    $this->updateStore();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function updateItemById($type, $id, $item) {
    $item['id'] = $id;
    $item['type'] = $type;

    $update_index = NULL;
    $old_item = NULL;
    foreach ($this->values['items'] as $index => $line_item) {
      if ($line_item['type'] == $type && $line_item['id'] == $id) {
        $update_index = $index;
        $old_item = $line_item;
        break;
      }
    }

    if (empty($old_item)) {
      throw new \Exception('Cannot update missing item');
    }

    return $this->updateItem($update_index, $item);
  }

  /**
   * {@inheritdoc}
   */
  public function updateItemQuantity($index, $quantity) {
    $old_item = $this->values['items'][$index];
    return $this->updateItemQuantityById($old_item['type'], $old_item['id'], $quantity);
  }

  /**
   * {@inheritdoc}
   */
  public function updateItemQuantityById($type, $id, $quantity) {
    $old_item = $this->getItemById($type, $id);
    if (empty($old_item)) {
      throw new \Exception('Cannot update missing item', 1001);
    }

    if (empty($quantity) || $quantity <= 0) {
      return $this->removeItemById($type, $id);
    }

    $new_item = NULL;
    foreach ($this->values['items'] as $index => $line_item) {
      if ($line_item['type'] == $type && $line_item['id'] == $id) {
        $this->values['items'][$index]['quantity'] = $quantity;
        $new_item = $this->values['items'][$index];
        break;
      }
    }
    $this->onCartUpdate(self::ITEM_UPDATE, $new_item, $old_item);
    $this->updateStore();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem($index) {
    $old_item = $this->values['items'][$index];
    return $this->removeItemById($old_item['type'], $old_item['id']);
  }

  /**
   * {@inheritdoc}
   */
  public function removeItemById($type, $id) {
    $old_item = $this->getItemById($type, $id);
    if (empty($old_item)) {
      throw new \Exception('Cannot remove missing item');
    }
    $new_item_list = array_filter($this->values['items'], function ($item) use ($type, $id) {
      return !($item['type'] == $type && $item['id'] == $id);
    });
    $this->values['items'] = array_values($new_item_list);

    $this->onCartUpdate(self::ITEM_REMOVE, NULL, $old_item);
    $this->updateStore();
    return $this;

  }

  /**
   * {@inheritdoc}
   */
  public function &getOrder() {
    return $this->order;
  }

  /**
   * {@inheritdoc}
   */
  public function placeOrder() {
    return $this->order->save();
  }

  /**
   * Load product.
   *
   * @param int $pid
   *   Product ID.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface|null
   *   Product entity.
   */
  protected function loadProduct($pid) {
    try {
      // @codingStandardsIgnoreStart
      $storage = \Drupal::entityTypeManager()->getStorage('product');
      // @codingStandardsIgnoreEnd

      return $storage->load($pid);
    }
    catch (\Exception $e) {
      // @todo handler error.
    }

    return NULL;
  }

  /**
   * Load currency.
   *
   * @param string $currency
   *   Currency ID.
   *
   * @return \Drupal\currency\Entity\CurrencyInterface|null
   *   Product entity.
   */
  protected function loadCurrency($currency) {
    try {
      // @codingStandardsIgnoreStart
      $storage = \Drupal::entityTypeManager()->getStorage('currency');
      // @codingStandardsIgnoreEnd

      return $storage->load($currency);
    }
    catch (\Exception $e) {
      // @todo handler error.
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleHandler() {
    if (!$this->moduleHandler) {
      $this->moduleHandler = \Drupal::moduleHandler();
    }
    return $this->moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function setPriceFactory(PriceFactoryInterface $price_factory) {
    $this->priceFactory = $price_factory;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriceFactory() {
    if (!$this->priceFactory) {
      $this->priceFactory = \Drupal::service('price_factory');
    }
    return $this->priceFactory;
  }

  /**
   * On cart update.
   *
   * @param string $type
   *   Update type.
   * @param array|null $item
   *   New value.
   * @param array|null $old_item
   *   Old value.
   */
  protected function onCartUpdate($type, $item, $old_item) {
    $this->shippingPrice = NULL;
    $implementations = $this->getModuleHandler()->getImplementations('arch_cart_change');
    foreach ($implementations as $module) {
      $function = $module . '_arch_cart_change';
      $function($type, $item, $old_item, $this->values['items'], $this);
    }

    $hook = NULL;
    switch ($type) {
      case self::ITEM_NEW:
        $hook = 'arch_cart_item_new';
        break;

      case self::ITEM_UPDATE:
        $hook = 'arch_cart_item_update';
        break;

      case self::ITEM_REMOVE:
        $hook = 'arch_cart_item_remove';
        break;
    }

    if ($hook) {
      $implementations = $this->getModuleHandler()->getImplementations($hook);
      foreach ($implementations as $module) {
        $function = $module . '_' . $hook;
        $function($item, $old_item, $this->values['items'], $this);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getShippingPrice($force_update = FALSE) {
    if ($this->shippingPrice && !$force_update) {
      return $this->shippingPrice;
    }

    $shipping_method = $this->order->getShippingMethod();
    $hook = ['shipping_price'];
    if (empty($shipping_method)) {
      $shipping_price = $this->getDefaultPriceValues();
    }
    else {
      $hook[] = 'shipping_price_' . $shipping_method->getPluginId();
      $shipping_price = $shipping_method->getShippingPrice($this->order);
    }

    if (is_array($shipping_price)) {
      $shipping_price = $this->getPriceFactory()->getInstance($shipping_price);
    }

    $this->getModuleHandler()->alter($hook, $shipping_price, $this, $this->order);
    if (
      !empty($shipping_price)
      && !($shipping_price instanceof PriceInterface)
    ) {
      throw new \TypeError('Shipping price should be PriceInterface instance!');
    }

    $this->shippingPrice = $shipping_price;
    return $shipping_price;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentFee($force_update = FALSE) {
    if ($this->paymentFee && !$force_update) {
      return $this->paymentFee;
    }

    $payment_method = $this->order->getPaymentMethod();
    if (empty($payment_method)) {
      return NULL;
    }

    $hook = ['payment_method_fee'];
    if (empty($payment_method)) {
      $payment_fee = $this->getDefaultPriceValues();
    }
    else {
      $hook[] = 'payment_method_fee_' . $payment_method->getPluginId();
      $payment_fee = $payment_method->getPaymentFee($this->order);
    }

    if (empty($payment_fee)) {
      $payment_fee = $this->getDefaultPriceValues();
    }

    if (is_array($payment_fee)) {
      $payment_fee = $this->getPriceFactory()->getInstance($payment_fee);
    }

    $payment_method_settings = $payment_method->getSettings();
    $this->getModuleHandler()->alter($hook, $this->order, $payment_fee, $payment_method_settings);
    if (
      !empty($payment_fee)
      && !($payment_fee instanceof PriceInterface)
    ) {
      throw new \TypeError('Payment fee should be PriceInterface instance!');
    }

    $this->paymentFee = $payment_fee;
    return $payment_fee;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultPriceValues(array $default_price_values) {
    $this->defaultPrice = $default_price_values;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTotalBaseValues(array $total_base_values) {
    $this->totalBaseValues = $total_base_values;
    return $this;
  }

  /**
   * Get default price values.
   *
   * @return array
   *   Default price values.
   */
  protected function getDefaultPriceValues() {
    return ((array) $this->defaultPrice) + [
      'base' => 'gross',
      'price_type' => 'default',
      'currency' => 'EUR',
      'net' => 0,
      'gross' => 0,
      'vat_category' => 'custom',
      'vat_rate' => 0,
      'vat_value' => 0,
      'date_from' => NULL,
      'date_to' => NULL,
    ];
  }

}
