<?php

namespace Drupal\arch_order\Plugin\Field\FieldWidget;

use Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'order_line_item_widget' widget.
 *
 * @FieldWidget(
 *   id = "order_line_item_widget",
 *   label = @Translation("Line item widget", context = "arch_order"),
 *   field_types = {
 *     "order_line_item"
 *   }
 * )
 */
class OrderLineItemWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Field\FieldItemList $items */
    $value = $items->get($delta)->getValue() + [
      'created' => NULL,
      'type' => NULL,
      'product_id' => NULL,
      'quantity' => NULL,
      'product_bundle' => NULL,
      'price_net' => NULL,
      'price_gross' => NULL,
      'price_vat_rate' => NULL,
      'price_vat_cat_name' => NULL,
      'price_vat_amount' => NULL,
      'calculated_net' => NULL,
      'calculated_gross' => NULL,
      'calculated_vat_rate' => NULL,
      'calculated_vat_cat_name' => NULL,
      'calculated_vat_amount' => NULL,
      'reason_of_diff' => NULL,
      'data' => NULL,
    ];

    $element['created'] = [
      '#title' => $this->t('Created on', [], ['context' => 'arch_line_item']),
      '#type' => 'hidden',
      '#value' => !empty($value['created']) ? $value['created'] : NULL,
    ];

    $element['type'] = [
      '#title' => $this->t('Type', [], ['context' => 'arch_line_item']),
      '#type' => 'select',
      '#options' => ['' => $this->t('- Select -')] + $this->getTypeOptions(),
      '#required' => $element['#required'],
      '#default_value' => $value['type'],
    ];

    $element['product_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Product ID', [], ['context' => 'arch_line_item']),
      '#required' => $element['#required'],
      '#size' => 10,
      '#max_length' => 10,
      '#default_value' => $value['product_id'],
    ];

    $element['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity', [], ['context' => 'arch_line_item']),
      '#required' => $element['#required'],
      '#size' => 10,
      '#max_length' => 10,
      '#default_value' => $value['quantity'],
    ];

    $element['product_bundle'] = [
      '#title' => $this->t('Product type', [], ['context' => 'arch_product']),
      '#type' => 'textfield',
      '#required' => $element['#required'],
      '#default_value' => $value['product_bundle'],
    ];

    $element['price_net'] = [
      '#type' => 'number',
      '#step' => 'any',
      '#min' => 0,
      '#required' => $element['#required'],
      '#title' => $this->t('Net price', [], ['context' => 'arch_price']),
      '#default_value' => $value['price_net'],
    ];

    $element['price_gross'] = [
      '#type' => 'number',
      '#step' => 'any',
      '#min' => 0,
      '#required' => $element['#required'],
      '#title' => $this->t('Gross price', [], ['context' => 'arch_price']),
      '#default_value' => $value['price_gross'],
    ];

    $element['price_vat_rate'] = [
      '#type' => 'number',
      '#step' => 'any',
      '#min' => 0,
      '#required' => $element['#required'],
      '#title' => $this->t('VAT rate', [], ['context' => 'arch_price']),
      '#default_value' => $value['price_vat_rate'] ? $value['price_vat_rate'] : NULL,
    ];

    $element['price_vat_amount'] = [
      '#type' => 'number',
      '#step' => 'any',
      '#min' => 0,
      '#title' => $this->t('VAT amount', [], ['context' => 'arch_price']),
      '#required' => $element['#required'],
      '#default_value' => $value['price_vat_amount'] ? $value['price_vat_amount'] : NULL,
    ];

    $element['price_vat_cat_name'] = [
      '#title' => $this->t('VAT category', [], ['context' => 'arch_line_item']),
      '#type' => 'textfield',
      '#required' => $element['#required'],
      '#default_value' => $value['price_vat_cat_name'],
    ];

    $element['calculated_net'] = [
      '#type' => 'number',
      '#step' => 'any',
      '#min' => 0,
      '#required' => $element['#required'],
      '#title' => $this->t('Calculated net price', [], ['context' => 'arch_line_item']),
      '#default_value' => $value['calculated_net'],
    ];

    $element['calculated_gross'] = [
      '#type' => 'number',
      '#step' => 'any',
      '#min' => 0,
      '#required' => $element['#required'],
      '#title' => $this->t('Calculated gross price', [], ['context' => 'arch_line_item']),
      '#default_value' => $value['calculated_gross'],
    ];

    $element['calculated_vat_rate'] = [
      '#type' => 'number',
      '#step' => 'any',
      '#min' => 0,
      '#required' => $element['#required'],
      '#title' => $this->t('Calculated VAT rate', [], ['context' => 'arch_line_item']),
      '#default_value' => $value['calculated_vat_rate'] ? $value['calculated_vat_rate'] : NULL,
    ];

    $element['calculated_vat_amount'] = [
      '#type' => 'number',
      '#step' => 'any',
      '#min' => 0,
      '#title' => $this->t('Calculated VAT amount', [], ['context' => 'arch_line_item']),
      '#required' => $element['#required'],
      '#default_value' => $value['calculated_vat_amount'] ? $value['calculated_vat_amount'] : NULL,
    ];

    $element['calculated_vat_cat_name'] = [
      '#title' => $this->t('Calculated VAT category', [], ['context' => 'arch_line_item']),
      '#type' => 'textfield',
      '#required' => $element['#required'],
      '#default_value' => $value['calculated_vat_cat_name'],
    ];

    $element['reason_of_diff'] = [
      '#title' => $this->t('Reason of diff', [], ['context' => 'arch_line_item']),
      '#type' => 'textfield',
      '#default_value' => $value['reason_of_diff'],
    ];

    $element['data'] = [
      '#title' => $this->t('Options for the line item', [], ['context' => 'arch_line_item']),
      '#type' => 'textarea',
      '#default_value' => $value['data'],
    ];

    return $element;
  }

  /**
   * Order type options.
   *
   * @return array
   *   Options.
   */
  public static function getTypeOptions() {
    // @todo define through hook.
    return [
      OrderLineItemInterface::ORDER_LINE_ITEM_TYPE_PRODUCT => t('Product', [], ['context' => 'arch_product']),
      OrderLineItemInterface::ORDER_LINE_ITEM_TYPE_DISCOUNT => t('Discount', [], ['context' => 'arch_discount']),
      OrderLineItemInterface::ORDER_LINE_ITEM_TYPE_SHIPPING => t('Shipping', [], ['context' => 'arch_shipping']),
      OrderLineItemInterface::ORDER_LINE_ITEM_TYPE_PAYMENT_FEE => t('Payment fee', [], ['context' => 'arch_shipping']),
    ];
  }

}
