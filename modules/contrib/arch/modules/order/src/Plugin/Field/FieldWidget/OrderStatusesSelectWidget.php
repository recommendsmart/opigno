<?php

namespace Drupal\arch_order\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Order Statuses' widget.
 *
 * @FieldWidget(
 *   id = "order_statuses_select",
 *   label = @Translation("Order status select", context = "arch_order_status"),
 *   field_types = {
 *     "string",
 *     "order_status"
 *   }
 * )
 */
class OrderStatusesSelectWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'order_statuses_select',
      '#default_value' => $items[$delta]->value,
    ];

    return $element;
  }

}
