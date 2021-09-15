<?php

namespace Drupal\arch_stock\Plugin\Field\FieldType;

use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

/**
 * Plugin implementation of the 'stock' field type.
 *
 * @FieldType(
 *   id = "stock",
 *   label = @Translation("Stock", context = "arch_stock"),
 *   default_widget = "stock_default",
 *   default_formatter = "stock_default",
 *   list_class = "\Drupal\arch_stock\Plugin\Field\FieldType\StockFieldItemList"
 * )
 */
class Stock extends FieldItemBase implements StockInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['warehouse'] = DataReferenceTargetDefinition::create('string')
      ->setLabel(t('Warehouse ID', [], ['context' => 'arch_stock']));

    $properties['warehouse_entity'] = DataReferenceDefinition::create('entity')
      ->setLabel(t('Warehouse entity', [], ['context' => 'arch_stock']))
      // The entity object is computed out of the entity ID.
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create('warehouse'))
      // We can add a constraint for the target entity type. The list of
      // referenceable bundles is a field setting, so the corresponding
      // constraint is added dynamically in ::getConstraints().
      ->addConstraint('EntityType', 'warehouse');

    $properties['quantity'] = DataDefinition::create('float')
      ->setLabel(t('Quantity', [], ['context' => 'arch_stock']))
      ->setSetting('precision', 2)
      ->setSetting('size', 14);

    $properties['cart_quantity'] = DataDefinition::create('float')
      ->setLabel(t('Cart quantity', [], ['context' => 'arch_stock']))
      ->setSetting('precision', 2)
      ->setSetting('size', 14);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'warehouse' => [
          'type' => 'varchar_ascii',
          'length' => 32,
        ],
        'quantity' => [
          'type' => 'numeric',
          'precision' => 14,
          'scale' => 2,
        ],
        'cart_quantity' => [
          'type' => 'numeric',
          'precision' => 14,
          'scale' => 2,
        ],
      ],
      'indexed' => [
        'type' => ['warehouse'],
        'stock' => ['warehouse', 'quantity', 'cart_quantity'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getWarehouseId() {
    return $this->get('warehouse')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getWarehouse() {
    $warehouse_id = $this->getWarehouseId();
    $storage = $this->getEntityTypeManager()->getStorage('warehouse');
    return $storage->load($warehouse_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantity() {
    return round((float) $this->get('quantity')->getValue(), 2);
  }

  /**
   * {@inheritdoc}
   */
  public function getCartQuantity() {
    return round((float) $this->get('cart_quantity')->getValue(), 2);
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    return $this->getQuantity() - $this->getCartQuantity() > 0;
  }

  /**
   * Entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   Entity type manager.
   */
  protected function getEntityTypeManager() {
    // @codingStandardsIgnoreStart
    return \Drupal::entityTypeManager();
    // @codingStandardsIgnoreEnd
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if (
      !empty($this->cart_quantity)
      || !empty($this->quantity)
    ) {
      return FALSE;
    }

    return TRUE;
  }

}
