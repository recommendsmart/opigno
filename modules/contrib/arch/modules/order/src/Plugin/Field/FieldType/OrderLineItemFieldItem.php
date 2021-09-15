<?php

namespace Drupal\arch_order\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'order_line_item' field type.
 *
 * @FieldType (
 *   id = "order_line_item",
 *   label = @Translation("Line item", context = "arch_order"),
 *   default_widget = "order_line_item_widget",
 *   default_formatter = "order_line_item_formatter",
 *   cardinality = \Drupal\Core\Field\FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
 * )
 */
class OrderLineItemFieldItem extends FieldItemBase implements OrderLineItemInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'created' => [
          'description' => 'Created date',
          'type' => 'int',
          'length' => 20,
        ],
        'type' => [
          'type' => 'int',
          'length' => 2,
        ],
        'quantity' => [
          'type' => 'numeric',
          'precision' => 14,
          'scale' => 2,
        ],
        'product_id' => [
          'type' => 'int',
          'length' => 10,
        ],
        'product_bundle' => [
          'type' => 'varchar',
          'length' => 32,
        ],
        'price_net' => [
          'type' => 'numeric',
          'precision' => 14,
          'scale' => 3,
        ],
        'price_gross' => [
          'type' => 'numeric',
          'precision' => 14,
          'scale' => 3,
        ],
        'price_vat_rate' => [
          'type' => 'numeric',
          'precision' => 8,
          'scale' => 4,
        ],
        'price_vat_cat_name' => [
          'type' => 'varchar_ascii',
          'length' => 255,
        ],
        'price_vat_amount' => [
          'type' => 'numeric',
          'precision' => 14,
          'scale' => 3,
        ],
        'calculated_net' => [
          'type' => 'numeric',
          'precision' => 14,
          'scale' => 3,
        ],
        'calculated_gross' => [
          'type' => 'numeric',
          'precision' => 14,
          'scale' => 3,
        ],
        'calculated_vat_rate' => [
          'type' => 'numeric',
          'precision' => 8,
          'scale' => 4,
        ],
        'calculated_vat_cat_name' => [
          'type' => 'varchar_ascii',
          'length' => 255,
        ],
        'calculated_vat_amount' => [
          'type' => 'numeric',
          'precision' => 14,
          'scale' => 3,
        ],
        'reason_of_diff' => [
          'type' => 'varchar_ascii',
          'length' => 255,
        ],
        'data' => [
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
      'indexed' => [
        'product' => ['product_id'],
        'type' => ['type'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['created'] = DataDefinition::create('integer')
      ->setLabel(t('Created on', [], ['context' => 'arch_line_item']));

    $properties['type'] = DataDefinition::create('integer')
      ->setLabel(t('Type', [], ['context' => 'arch_line_item']))
      ->setRequired(TRUE);

    $properties['quantity'] = DataDefinition::create('float')
      ->setLabel(t('Quantity', [], ['context' => 'arch_line_item']))
      ->setSetting('precision', 2)
      ->setSetting('size', 14);

    $properties['product_id'] = DataDefinition::create('integer')
      ->setLabel(t('Product ID', [], ['context' => 'arch_line_item']))
      ->setRequired(TRUE);

    $properties['product_bundle'] = DataDefinition::create('string')
      ->setLabel(t('Product type', [], ['context' => 'arch_product']))
      ->setRequired(TRUE);

    $properties['price_net'] = DataDefinition::create('float')
      ->setLabel(t('Net price', [], ['context' => 'arch_price']))
      ->setSetting('precision', 3)
      ->setSetting('size', 14)
      ->setRequired(TRUE);

    $properties['price_gross'] = DataDefinition::create('float')
      ->setLabel(t('Gross price', [], ['context' => 'arch_price']))
      ->setSetting('precision', 3)
      ->setSetting('size', 14)
      ->setRequired(TRUE);

    $properties['price_vat_rate'] = DataDefinition::create('float')
      ->setLabel(t('VAT rate', [], ['context' => 'arch_price']))
      ->setSetting('precision', 4)
      ->setSetting('size', 8)
      ->setRequired(TRUE);

    $properties['price_vat_cat_name'] = DataDefinition::create('string')
      ->setLabel(t('VAT category', [], ['context' => 'arch_line_item']))
      ->addConstraint('Length', ['max' => 32]);

    $properties['price_vat_amount'] = DataDefinition::create('float')
      ->setLabel(t('VAT amount', [], ['context' => 'arch_price']))
      ->setSetting('precision', 3)
      ->setSetting('size', 14)
      ->setRequired(TRUE);

    $properties['calculated_net'] = DataDefinition::create('float')
      ->setLabel(t('Calculated net price', [], ['context' => 'arch_line_item']))
      ->setSetting('precision', 3)
      ->setSetting('size', 14)
      ->setRequired(TRUE);

    $properties['calculated_gross'] = DataDefinition::create('float')
      ->setLabel(t('Calculated gross price', [], ['context' => 'arch_line_item']))
      ->setSetting('precision', 3)
      ->setSetting('size', 14)
      ->setRequired(TRUE);

    $properties['calculated_vat_rate'] = DataDefinition::create('float')
      ->setLabel(t('Calculated VAT rate', [], ['context' => 'arch_line_item']))
      ->setSetting('precision', 4)
      ->setSetting('size', 8)
      ->setRequired(TRUE);

    $properties['calculated_vat_cat_name'] = DataDefinition::create('string')
      ->setLabel(t('Calculated VAT category', [], ['context' => 'arch_line_item']))
      ->addConstraint('Length', ['max' => 32]);

    $properties['calculated_vat_amount'] = DataDefinition::create('float')
      ->setLabel(t('Calculated VAT amount', [], ['context' => 'arch_line_item']))
      ->setSetting('precision', 3)
      ->setSetting('size', 14)
      ->setRequired(TRUE);

    $properties['reason_of_diff'] = DataDefinition::create('string')
      ->setLabel(t('Reason of diff', [], ['context' => 'arch_line_item']));

    $properties['data'] = DataDefinition::create('string')
      ->setLabel(t('Serialized array of options for the line item.', [], ['context' => 'arch_line_item']));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    foreach ($this->definition->getPropertyDefinitions() as $name => $definition) {
      if ($this->get($name)->getValue()) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLineItemTypeId() {
    return (int) $this->get('type')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function isProduct() {
    return $this->getLineItemTypeId() === self::ORDER_LINE_ITEM_TYPE_PRODUCT;
  }

  /**
   * {@inheritdoc}
   */
  public function isDiscount() {
    return $this->getLineItemTypeId() === self::ORDER_LINE_ITEM_TYPE_DISCOUNT;
  }

  /**
   * {@inheritdoc}
   */
  public function isShipping() {
    return $this->getLineItemTypeId() === self::ORDER_LINE_ITEM_TYPE_SHIPPING;
  }

  /**
   * {@inheritdoc}
   */
  public function isPaymentFee() {
    return $this->getLineItemTypeId() === self::ORDER_LINE_ITEM_TYPE_PAYMENT_FEE;
  }

  /**
   * {@inheritdoc}
   */
  public function getProduct() {
    $pid = (int) $this->get('product_id')->getValue();
    /** @var \Drupal\arch_product\Entity\Storage\ProductStorageInterface $product_storage */
    $product_storage = $this->getEntityTypeManager()->getStorage('product');
    return $product_storage->load($pid);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantity() {
    /** @var \Drupal\Core\TypedData\Plugin\DataType\FloatData $property */
    $property = $this->get('quantity');
    if (empty($property->getValue())) {
      return NULL;
    }
    return (float) $property->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setQuantity($quantity) {
    $this->set('qantity', $quantity);
    return $this;
  }

  /**
   * Get entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   Entity type manager.
   */
  protected function getEntityTypeManager() {
    if (!isset($this->entityTypeManager)) {
      // @codingStandardsIgnoreStart
      $this->entityTypeManager = \Drupal::entityTypeManager();
      // @codingStandardsIgnoreEnd
    }
    return $this->entityTypeManager;
  }

}
