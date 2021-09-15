<?php

namespace Drupal\arch_stock;

use Drupal\arch_stock\Entity\Warehouse;
use Drupal\arch_stock\Entity\WarehouseInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Default warehouse implementation.
 *
 * @package Drupal\arch_stock
 */
class WarehouseDefault {

  use StringTranslationTrait;

  /**
   * The default warehouse.
   *
   * @var \Drupal\arch_stock\Entity\WarehouseInterface
   */
  protected $warehouse;

  /**
   * WarehouseDefault constructor.
   *
   * @param array $values
   *   The properties used to construct the default warehouse.
   */
  public function __construct(array $values = NULL) {
    if (!isset($values)) {
      $values = [
        'id' => 'default',
        'name' => $this->t('Default', [], ['context' => 'arch_stock_warehouse']),
        'locked' => TRUE,
        'status' => TRUE,
        'weight' => -1000,
      ];
    }
    $this->set(Warehouse::create($values));
  }

  /**
   * Gets the default warehouse.
   *
   * @return \Drupal\arch_stock\Entity\WarehouseInterface
   *   The default warehouse.
   */
  public function get() {
    return $this->warehouse;
  }

  /**
   * Sets the default warehouse.
   *
   * @param \Drupal\arch_stock\Entity\WarehouseInterface $warehouse
   *   The default warehouse.
   */
  public function set(WarehouseInterface $warehouse) {
    $this->warehouse = $warehouse;
  }

}
