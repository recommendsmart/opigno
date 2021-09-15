<?php

namespace Drupal\arch_order\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\arch_order\Plugin\Field\FieldWidget\OrderLineItemWidget;

/**
 * Plugin implementation of the 'order_line_item_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "order_line_item_formatter",
 *   module = "arch_order",
 *   label = @Translation("Line item", context = "arch_order"),
 *   field_types = {
 *     "order_line_item"
 *   }
 * )
 */
class OrderLineItemFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $tabledata = [];

    $values = [
      'type' => $this->t('Type', [], ['context' => 'arch_line_item']),
      'product_id' => $this->t('Product ID', [], ['context' => 'arch_line_item']),
      'product_bundle' => $this->t('Product type', [], ['context' => 'arch_product']),
      'price_net' => $this->t('Net price', [], ['context' => 'arch_price']),
      'price_gross' => $this->t('Gross price', [], ['context' => 'arch_price']),
      'price_vat_rate' => $this->t('VAT rate', [], ['context' => 'arch_price']),
      'price_vat_cat_name' => $this->t('VAT category', [], ['context' => 'arch_line_item']),
      'price_vat_amount' => $this->t('VAT amount', [], ['context' => 'arch_price']),
      'calculated_net' => $this->t('Calculated net price', [], ['context' => 'arch_line_item']),
      'calculated_gross' => $this->t('Calculated gross price', [], ['context' => 'arch_line_item']),
      'calculated_vat_rate' => $this->t('Calculated VAT rate', [], ['context' => 'arch_line_item']),
      'calculated_vat_cat_name' => $this->t('Calculated VAT category', [], ['context' => 'arch_line_item']),
      'calculated_vat_amount' => $this->t('Calculated VAT amount', [], ['context' => 'arch_line_item']),
      'reason_of_diff' => $this->t('Reason of diff', [], ['context' => 'arch_line_item']),
      'data' => $this->t('Options', [], ['context' => 'arch_line_item']),
    ];
    foreach ($items as $delta => $table) {
      foreach ($values as $value_name => $value_label) {
        $value = isset($table->{$value_name}) ? $table->{$value_name} : NULL;
        if ($value_name == 'type') {
          $type_options = OrderLineItemWidget::getTypeOptions();
          $value = $type_options[$value];
        }
        $tabledata[$delta][] = [
          'data' => $value,
          'class' => ['value', 'value-name-' . $value_name],
        ];
      }
    }

    if (empty($tabledata)) {
      return [];
    }

    $header = [];
    foreach ($values as $value_label) {
      $header[] = [
        'data' => $value_label,
        'class' => ['header-value'],
      ];
    }

    $render_array = [];
    $render_array['line_items'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $tabledata,
      '#attributes' => [
        'class' => [
          'order-line-items-table',
        ],
      ],
    ];

    $elements[] = $render_array;

    return $elements;
  }

}
