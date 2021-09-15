<?php

namespace Drupal\arch_stock;

use Drupal\arch_product\Entity\ProductTypeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Stock info.
 *
 * @package Drupal\arch_stock
 */
class StockInfo implements StockInfoInterface, ContainerInjectionInterface {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * Field config storage.
   *
   * @var \Drupal\field\FieldConfigStorage
   */
  protected $fieldConfigStorage;

  /**
   * StockInfo constructor.
   *
   * @param \Drupal\Core\Database\Connection $db
   *   Database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    Connection $db,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->db = $db;
    $this->fieldConfigStorage = $entity_type_manager->getStorage('field_config');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function typeHasStockData(ProductTypeInterface $product_type) {
    /** @var \Drupal\field\Entity\FieldConfig $stock_field */
    $stock_field = $this->fieldConfigStorage->load('product.' . $product_type->id() . '.stock');
    if (empty($stock_field)) {
      return FALSE;
    }

    $select = $this->db->select('product__stock', 's');
    $select->condition('bundle', $product_type->id());
    $count = (int) $select->countQuery()->execute()->fetchField();
    return $count > 0;
  }

}
