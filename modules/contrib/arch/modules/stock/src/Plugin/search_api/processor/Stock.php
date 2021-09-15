<?php

namespace Drupal\arch_stock\Plugin\search_api\processor;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds stock info fields to indexed data.
 *
 * @SearchApiProcessor(
 *   id = "search_api_arch_stock_value",
 *   label = @Translation("Stock value"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class Stock extends ProcessorPluginBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Warehouse storage.
   *
   * @var \Drupal\arch_stock\Entity\Storage\WarehouseStorageInterface
   */
  protected $warehouseStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->setEntityTypeManager($container->get('entity_type.manager'));
    return $processor;
  }

  /**
   * Sets entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->warehouseStorage = $entity_type_manager->getStorage('warehouse');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if ($datasource) {
      /** @var \Drupal\Core\Entity\ContentEntityType $entity_type */
      $entity_type = $this->entityTypeManager->getDefinition($datasource->getEntityTypeId());
      if ($entity_type->entityClassImplements(ProductInterface::class)) {
        foreach ($this->getDefinitions($datasource) as $property => $definition) {
          $properties[$property] = new ProcessorProperty($definition);
        }
      }
    }

    return $properties;
  }

  /**
   * Get property definition arrays.
   *
   * @param \Drupal\search_api\Datasource\DatasourceInterface $datasource
   *   The datasource this set of properties belongs to.
   *
   * @return array
   *   Property definition array.
   */
  protected function getDefinitions(DatasourceInterface $datasource) {
    $properties = [];
    foreach ($this->warehouseStorage->loadMultiple() as $warehouse) {
      /** @var \Drupal\arch_stock\Entity\WarehouseInterface $warehouse */
      $properties['arch_stock_' . $warehouse->id()] = [
        'label' => $this->t('Warehouse: @warehouse stock', ['@warehouse' => $warehouse->label()], ['context' => 'arch_stock']),
        'type' => 'decimal',
        'fields' => ['quantity'],
        'computed' => TRUE,
        'processor_id' => $this->getPluginId(),
        'datasource' => $datasource,
        'is_list' => FALSE,
        'warehouse' => $warehouse->id(),
      ];
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $item->getOriginalObject()->getValue();
    if (
      !$product->hasField('stock')
      || $product->get('stock')->isEmpty()
    ) {
      return;
    }

    foreach ($product->get('stock')->getValue() as $value) {
      if (empty($value)) {
        continue;
      }

      $property = 'arch_stock_' . $value['warehouse'];
      $definitions = $this->getDefinitions($item->getDatasource());
      if (!isset($definitions[$property])) {
        continue;
      }

      $definition = $definitions[$property];

      if ($definition['warehouse'] != $value['warehouse']) {
        continue;
      }

      /** @var \Drupal\search_api\Item\Field[] $fields */
      $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), $item->getDatasourceId(), $property);
      if (empty($fields)) {
        continue;
      }

      foreach ($fields as $field) {
        $field->setValues([(float) $value['quantity']]);
      }
    }
  }

}
