<?php

namespace Drupal\arch_order\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;

/**
 * Defines the 'order_status' entity field item.
 *
 * @FieldType(
 *   id = "order_status",
 *   label = @Translation("Order status", context = "arch_order"),
 *   description = @Translation("An entity field referencing an order status.", context = "arch_order"),
 *   default_widget = "order_statuses_select",
 *   default_formatter = "string",
 *   no_ui = TRUE,
 *   constraints = {
 *     "ComplexData" = {
 *       "value" = {
 *         "Length" = {"max" = 32}
 *       }
 *     }
 *   }
 * )
 */
class OrderStatusItem extends FieldItemBase implements OptionsProviderInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Order status code', [], ['context' => 'arch_order_status']))
      ->setRequired(TRUE);

    $properties['order_status'] = DataReferenceDefinition::create('order_status')
      ->setLabel(t('Order status object', [], ['context' => 'arch_order_status']))
      ->setDescription(t('The referenced order status', [], ['context' => 'arch_order_status']))
      // The order status object is retrieved via the order status code.
      ->setComputed(TRUE)
      ->setReadOnly(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar_ascii',
          'length' => 32,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the language property, if no array
    // is given as this handles language codes and objects.
    if (isset($values) && !is_array($values)) {
      $this->set('order_status', $values, $notify);
    }
    else {
      // Make sure that the 'language' property gets set as 'value'.
      if (isset($values['value']) && !isset($values['order_status'])) {
        $values['order_status'] = $values['value'];
      }
      parent::setValue($values, $notify);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    $defaultOrderStatus = \Drupal::service('order.statuses')->getDefaultOrderStatus();
    if (!empty($defaultOrderStatus)) {
      $this->setValue(['value' => $defaultOrderStatus->id()], $notify);
    }
    else {
      // I hate fixed values.. Run you fouls..
      $this->setValue(['value' => 'cart'], $notify);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Make sure that the value and the language property stay in sync.
    if ($property_name == 'value') {
      $this->writePropertyValue('order_status', $this->value);
    }
    elseif ($property_name == 'order_status') {
      $this->writePropertyValue('value', $this->get('order_status')->getTargetIdentifier());
    }
    parent::onChange($property_name, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // @todo Implement this.
    $values['value'] = '@ORDER_STATUS_ITEM_SAMPLE_VALUE@';
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    $orderStatuses = \Drupal::service('order.statuses')->getOrderStatuses();
    return array_keys($orderStatuses);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    /** @var \Drupal\arch_order\Entity\OrderStatusInterface[] $orderStatuses */
    $orderStatuses = \Drupal::service('order.statuses')->getOrderStatuses();
    if (empty($orderStatuses)) {
      return [];
    }

    $options = [];
    foreach ($orderStatuses as $id => $orderStatus) {
      $options[$id] = $orderStatus->getLabel();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    return $this->getPossibleValues($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    return $this->getPossibleValues($account);
  }

}
