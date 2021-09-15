<?php

namespace Drupal\arch_stock\Access;

use Drupal\arch_stock\Entity\WarehouseInterface;
use Drupal\arch_stock\Entity\Storage\WarehouseStorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the stock module.
 */
class StockPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The warehouse storage.
   *
   * @var \Drupal\arch_stock\Entity\Storage\WarehouseStorageInterface
   */
  protected $warehouseStorage;

  /**
   * Constructs a StockPermissions instance.
   *
   * @param \Drupal\arch_stock\Entity\Storage\WarehouseStorageInterface $warehouse_storage
   *   Warehouse storage.
   */
  public function __construct(
    WarehouseStorageInterface $warehouse_storage
  ) {
    $this->warehouseStorage = $warehouse_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('warehouse')
    );
  }

  /**
   * Get stock permissions.
   *
   * @return array
   *   Permissions array.
   */
  public function permissions() {
    $permissions = [];
    foreach ($this->warehouseStorage->loadMultiple() as $warehouse) {
      /** @var \Drupal\arch_stock\Entity\WarehouseInterface $warehouse */
      $permissions += $this->buildPermissions($warehouse);
    }
    return $permissions;
  }

  /**
   * Builds a standard list of warehouse permissions.
   *
   * @param \Drupal\arch_stock\Entity\WarehouseInterface $warehouse
   *   The warehouse.
   *
   * @return array
   *   An array of permission names and descriptions.
   */
  protected function buildPermissions(WarehouseInterface $warehouse) {
    $id = $warehouse->id();
    $args = ['%warehouse' => $warehouse->label()];

    return [
      "purchase from {$id} stock" => [
        'title' => $this->t('Purchase from %warehouse stock', $args, ['context' => 'arch_stock']),
      ],
      "create {$id} stock" => [
        'title' => $this->t('Create %warehouse stock', $args, ['context' => 'arch_stock']),
        'restrict access' => TRUE,
      ],
      "delete {$id} stock" => [
        'title' => $this->t('Delete %warehouse stock', $args, ['context' => 'arch_stock']),
        'restrict access' => TRUE,
      ],
      "edit {$id} stock" => [
        'title' => $this->t('Edit %warehouse stock', $args, ['context' => 'arch_stock']),
        'restrict access' => TRUE,
      ],
    ];
  }

}
