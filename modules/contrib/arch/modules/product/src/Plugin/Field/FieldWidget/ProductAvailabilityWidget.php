<?php

namespace Drupal\arch_product\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'product_availability' widget.
 *
 * @FieldWidget(
 *   id = "product_availability_select",
 *   label = @Translation("Product availability select", context = "arch_product_availability"),
 *   field_types = {
 *     "product_availability"
 *   }
 * )
 */
class ProductAvailabilityWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'product_availability_select',
      '#default_value' => $items[$delta]->value,
    ];

    return $element;
  }

}
