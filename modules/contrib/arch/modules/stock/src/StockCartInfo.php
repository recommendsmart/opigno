<?php

namespace Drupal\arch_stock;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Stock cart info service.
 *
 * @package Drupal\arch_stock
 */
class StockCartInfo implements StockCartInfoInterface, ContainerInjectionInterface {

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Shared temp store.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $store;

  /**
   * Storage factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface
   */
  protected $storageFactory;

  /**
   * StockCartInfo constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_factory
   *   Key value storage factory.
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $temp_store_factory
   *   Shared temp store factory.
   */
  public function __construct(
    AccountInterface $current_user,
    KeyValueExpirableFactoryInterface $key_value_factory,
    SharedTempStoreFactory $temp_store_factory
  ) {
    $this->currentUser = $current_user;
    $this->storageFactory = $key_value_factory;
    $this->store = $temp_store_factory->get('arch_stock_in_cart');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('keyvalue.expirable'),
      $container->get('tempstore.shared')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function addItem($product_id, $quantity) {
    return $this->updateItem($product_id, $quantity);
  }

  /**
   * {@inheritdoc}
   */
  public function removeItem($product_id, $quantity) {
    $value = $this->quantityInCarts($product_id);
    $value -= $quantity;

    $this->setQuantityInCarts($product_id, $value);
    $this->saveOwnerData($product_id, NULL);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function updateItem($product_id, $quantity) {
    $total = $this->quantityInCarts($product_id);
    $current_owner_value = $this->getOwnerQuantity($product_id);

    $new_total = $total - $current_owner_value + $quantity;
    $this->setQuantityInCarts($product_id, $new_total);

    $this->saveOwnerData($product_id, $quantity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function quantityInCarts($product_id) {
    return (float) $this->store->get('product:' . $product_id);
  }

  /**
   * Set total quantity in carts for product.
   *
   * @param int $product_id
   *   Product ID.
   * @param float $quantity
   *   Total quantity in carts.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function setQuantityInCarts($product_id, $quantity) {
    if (empty($quantity)) {
      $this->store->delete('product:' . $product_id);
    }
    else {
      $this->store->set('product:' . $product_id, $quantity);
    }
  }

  /**
   * Set owner quantity.
   *
   * @param string|int $owner
   *   Session or User ID.
   * @param int $product_id
   *   Product ID.
   * @param float|null $quantity
   *   New quantity.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function setOwnerQuantity($owner, $product_id, $quantity = NULL) {
    $key = $owner . ':cart';
    $data = $this->store->get($key);
    if (empty($quantity)) {
      unset($data[$product_id]);
    }
    else {
      $data[$product_id] = $quantity;
    }
    $this->store->set($key, $data);
  }

  /**
   * Get current quantity for current user.
   *
   * @param int $product_id
   *   Product ID.
   *
   * @return float
   *   Current quantity.
   */
  protected function getOwnerQuantity($product_id) {
    $owner = $this->getOwner();
    $key = $owner . ':cart';
    $data = $this->store->get($key);
    if (empty($data[$product_id])) {
      return 0;
    }
    return (float) $data[$product_id];
  }

  /**
   * Get owner.
   *
   * @return string
   *   User or Session ID.
   */
  protected function getOwner() {
    return $this->currentUser->id() ?: session_id();
  }

  /**
   * Save owner data.
   *
   * @param int $product_id
   *   Product ID.
   * @param float|null $quantity
   *   New quantity.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function saveOwnerData($product_id, $quantity = NULL) {
    $owner = $this->getOwner();
    $this->setOwnerQuantity($owner, $product_id, $quantity);
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    $tmp = $this->storageFactory->get('tempstore.private.arch_cart');
    $products = [];
    $owner_counts = [];

    foreach ($tmp->getAll() as $key => $cart_data) {
      if (substr($key, -5) != ':cart') {
        continue;
      }

      $owner = substr($key, 0, -5);

      if (!isset($owner_counts[$owner])) {
        $owner_counts[$owner] = [];
      }

      foreach ($cart_data->data['items'] as $item) {
        if ($item['type'] !== 'product') {
          continue;
        }

        $product_id = $item['id'];
        if (!isset($products[$product_id])) {
          $products[$product_id] = 0;
        }
        $products[$product_id] += $item['quantity'];
        $owner_counts[$owner][$product_id] = $item['quantity'];
      }
    }

    $storage = $this->storageFactory->get('tempstore.shared.arch_stock_in_cart');
    $storage->deleteAll();
    foreach ($products as $product_id => $quantity) {
      $this->setQuantityInCarts($product_id, $quantity);
    }
    foreach ($owner_counts as $owner => $cart_products) {
      foreach ($cart_products as $product_id => $owner_product_quantity) {
        $this->setOwnerQuantity($owner, $product_id, $owner_product_quantity);
      }
    }
  }

}
