<?php

namespace Drupal\arch_stock\Manager;

use Drupal\arch_stock\Entity\Warehouse;
use Drupal\arch_stock\WarehouseDefault;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Warehouse manager.
 *
 * @package Drupal\arch_stock\Manager
 */
class WarehouseManager implements WarehouseManagerInterface, ContainerInjectionInterface {

  /**
   * Defined warehouse.
   *
   * @var \Drupal\arch_stock\Entity\WarehouseInterface[]
   */
  protected $warehouses;

  /**
   * Default warehouse.
   *
   * @var \Drupal\arch_stock\WarehouseDefault
   */
  protected $defaultWarehouse;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * WarehouseManager constructor.
   *
   * @param \Drupal\arch_stock\WarehouseDefault $default_warehouse
   *   Default warehouse.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(
    WarehouseDefault $default_warehouse,
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler
  ) {
    $this->defaultWarehouse = $default_warehouse;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('warehouse.default'),
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * Get default warehouse.
   *
   * @return \Drupal\arch_stock\Entity\WarehouseInterface
   *   Default warehouse.
   */
  public function getDefaultWarehouse() {
    return $this->defaultWarehouse->get();
  }

  /**
   * List of defined warehouses.
   *
   * @return \Drupal\arch_stock\Entity\WarehouseInterface[]
   *   Warehouse list.
   */
  public function getWarehouses() {
    if (!isset($this->warehouses)) {
      $default = $this->getDefaultWarehouse();
      $warehouses = [$default->id() => $default];

      $config_ids = $this->configFactory->listAll('arch_stock.warehouse.');
      foreach ($this->configFactory->loadMultiple($config_ids) as $config) {
        $data = $config->get();
        $warehouses[$data['id']] = Warehouse::create($data);
      }

      uasort($warehouses, '\Drupal\arch_stock\Entity\Warehouse::sort');
      $this->warehouses = $warehouses;
    }
    return $this->warehouses;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormOptions() {
    $options = [];
    foreach ($this->getWarehouses() as $warehouse) {
      if (!$warehouse->status()) {
        continue;
      }

      $options[$warehouse->id()] = $warehouse->label();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableWarehouses(AccountInterface $account) {
    $warehouses = array_filter($this->getWarehouses(), function ($warehouse) use ($account) {
      /** @var \Drupal\arch_stock\Entity\WarehouseInterface $warehouse */
      $result = $warehouse->access('view', $account, TRUE);
      return !$result->isForbidden();
    });

    $context = [
      'account' => $account,
    ];
    $this->moduleHandler->alter('stock_available_warehouses', $warehouses, $context);
    return $warehouses;
  }

}
