<?php

namespace Drupal\arch_stock;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\arch_product\Entity\ProductAvailability;
use Drupal\arch_product\Entity\ProductInterface;
use Drupal\arch_stock\Entity\WarehouseInterface;
use Drupal\arch_stock\Manager\WarehouseManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Stock keeper service.
 *
 * @package Drupal\arch_stock
 */
class StockKeeper implements StockKeeperInterface {

  /**
   * Product type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $productTypeStorage;

  /**
   * Warehouse manager.
   *
   * @var \Drupal\arch_stock\Manager\WarehouseManagerInterface
   */
  protected $warehouseManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * StockKeeper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\arch_stock\Manager\WarehouseManagerInterface $warehouse_manager
   *   Warehouse manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   Cache tag invalidator service.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   Lock backend.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    WarehouseManagerInterface $warehouse_manager,
    ModuleHandlerInterface $module_handler,
    CacheTagsInvalidatorInterface $cache_tags_invalidator,
    LockBackendInterface $lock
  ) {
    $this->productTypeStorage = $entity_type_manager->getStorage('product_type');
    $this->warehouseManager = $warehouse_manager;
    $this->moduleHandler = $module_handler;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->lock = $lock;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('warehouse.manager'),
      $container->get('module_handler'),
      $container->get('cache_tags.invalidator'),
      $container->get('lock')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isProductManagingStock(ProductInterface $product) {
    /** @var \Drupal\arch_product\Entity\ProductTypeInterface $type */
    $type = $this->productTypeStorage->load($product->bundle());
    return (bool) $type->getThirdPartySetting('arch_stock', 'stock_enable');
  }

  /**
   * {@inheritdoc}
   */
  public function reduceStock(
    ProductInterface $product,
    $sold_amount,
    OrderInterface $order,
    AccountInterface $account
  ) {
    $lock_name = 'arch_stock_reduce:product:' . $product->id();
    while ($this->lock->wait($lock_name, 0.2)) {
      $this->lock->acquire($lock_name);
      $this->doReduce($product, $sold_amount, $order, $account);
      $this->lock->release($lock_name);
      break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isNegativeStockAllowed(
    ProductInterface $product,
    AccountInterface $account
  ) {
    if ($product->getAvailability() === ProductAvailability::STATUS_NOT_AVAILABLE) {
      return FALSE;
    }
    $selected_warehouses = $this->selectWarehouses($account);
    return $this->allowNegativeStockForWarehouses($selected_warehouses, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalProductStock(
    ProductInterface $product,
    AccountInterface $account
  ) {
    $selected_warehouses = $this->selectWarehouses($account);
    $stock_values = $this->getCurrentStock($product);
    $total = 0;
    foreach ($stock_values as &$stock) {
      if (!in_array($stock['warehouse'], $selected_warehouses)) {
        continue;
      }
      $total += $stock['quantity'];
    }

    $this->moduleHandler->alter('product_stock', $total, $product, $account);

    return $total;
  }

  /**
   * {@inheritdoc}
   */
  public function hasProductEnoughStock(
    ProductInterface $product,
    AccountInterface $account,
    $amount = 1
  ) {
    if ($product->getAvailability() === ProductAvailability::STATUS_NOT_AVAILABLE) {
      return FALSE;
    }

    $selected_warehouses = $this->selectWarehouses($account);
    if ($this->allowNegativeStockForWarehouses($selected_warehouses, $account)) {
      return TRUE;
    }

    return $this->getTotalProductStock($product, $account) >= $amount;
  }

  /**
   * Do reduce.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   * @param float $sold_amount
   *   Sold amount of product.
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Customer account.
   *
   * @return bool
   *   Result.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function doReduce(
    ProductInterface $product,
    $sold_amount,
    OrderInterface $order,
    AccountInterface $account
  ) {
    $selected_warehouses = $this->selectWarehouses($account);
    $amount = $sold_amount;

    $stock_values = $this->getCurrentStock($product);
    $original_stock_values = $stock_values;
    foreach ($stock_values as &$stock) {
      if (!in_array($stock['warehouse'], $selected_warehouses)) {
        continue;
      }

      $available = $stock['quantity'];
      if ($available <= $amount) {
        $stock['quantity'] = 0;
        $amount -= $available;
      }
      else {
        $stock['quantity'] -= $amount;
        $amount = 0;
      }
    }

    if ($amount > 0) {
      if ($this->allowNegativeStockForWarehouses($selected_warehouses, $account)) {
        /** @var \Drupal\arch_stock\Entity\WarehouseInterface[] $warehouses */
        $warehouses = $this->warehouseManager->getAvailableWarehouses($account);
        foreach ($stock_values as &$stock) {
          if (!in_array($stock['warehouse'], $selected_warehouses)) {
            continue;
          }

          if (
            !empty($stock['warehouse'])
            && !empty($warehouses[$stock['warehouse']])
            && $warehouses[$stock['warehouse']]->allowNegative()
          ) {
            $stock['quantity'] -= $amount;
            $amount = 0;

            if (
              $stock['quantity'] <= 0
              && ($availability = $warehouses[$stock['warehouse']]->getOverBookedAvailability())
            ) {
              $product->setAvailability($availability);
            }
          }
        }
      }

      // @todo Handle over selling.
    }

    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product->set('stock', $stock_values);
    $product->setNewRevision(FALSE);
    $product->save();
    $this->moduleHandler->invokeAll('stock_reduced', [
      $sold_amount,
      $product,
      $order,
      $account,
      $original_stock_values,
    ]);

    // Invalidate cache.
    $this->cacheTagsInvalidator->invalidateTags($product->getCacheTagsToInvalidate());
    return TRUE;
  }

  /**
   * Get current stock of product.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   *
   * @return array
   *   Stock values.
   */
  protected function getCurrentStock(ProductInterface $product) {
    if (!$product->hasField('stock')) {
      return [];
    }
    return array_map(function ($item) {
      $item['quantity'] = (float) $item['quantity'];
      return $item;
    }, $product->get('stock')->getValue());
  }

  /**
   * {@inheritdoc}
   */
  public function selectWarehouses(AccountInterface $account) {
    $warehouses = $this->warehouseManager->getAvailableWarehouses($account);
    $list = array_map(function ($warehouse) {
      /** @var \Drupal\arch_stock\Entity\WarehouseInterface $warehouse */
      return $warehouse->id();
    }, $warehouses);

    $context = [
      'account' => $account,
      'warehouses' => $warehouses,
    ];
    $this->moduleHandler->alter('stock_keeper_selected_warehouses', $list, $context);
    return $list;
  }

  /**
   * Check if any of the warehouses with given IDs is allow negative stock.
   *
   * @param string[] $warehouse_ids
   *   Warehouse IDs.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account.
   *
   * @return bool
   *   Return TRUE if any of the warehouses with given ID is allow overbooking.
   */
  protected function allowNegativeStockForWarehouses(array $warehouse_ids, AccountInterface $account) {
    if (empty($warehouse_ids)) {
      return FALSE;
    }

    $warehouses = $this->warehouseManager->getWarehouses();
    foreach ($warehouses as $warehouse) {
      if (!in_array($warehouse->id(), $warehouse_ids)) {
        continue;
      }

      if ($this->allowNegativeStockForWarehouse($warehouse, $account)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Check if negative stock is allowed for account at warehouse.
   *
   * @param \Drupal\arch_stock\Entity\WarehouseInterface $warehouse
   *   Warehouse entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account.
   *
   * @return bool
   *   Returns TRUE if given user can access negative stock in warehouse.
   */
  protected function allowNegativeStockForWarehouse(WarehouseInterface $warehouse, AccountInterface $account) {
    $allow_negative = AccessResult::allowedIf($warehouse->allowNegative());
    $this->moduleHandler->alter('allow_negative_stock_for_warehouse', $allow_negative, $warehouse, $account);
    return $allow_negative->isAllowed();
  }

}
