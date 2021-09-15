<?php

namespace Drupal\arch_order\Element;

use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for selecting an order status.
 *
 * This does not render an actual form element, but always returns the value of
 * the default language. It is then extended by Language module via
 * language_element_info_alter() to provide a proper language selector.
 *
 * @see language_element_info_alter()
 *
 * @FormElement("order_statuses_select")
 */
class OrderStatusesSelect extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#default_value' => NULL,
    ];
  }

}
