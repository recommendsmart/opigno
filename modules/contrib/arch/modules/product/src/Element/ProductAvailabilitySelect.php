<?php

namespace Drupal\arch_product\Element;

use Drupal\arch_product\Entity\ProductAvailabilityInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for selecting a product availability.
 *
 * This does not render an actual form element, but always returns the value of
 * the default language. It is then extended by Language module via
 * arch_product_element_info_alter() to provide a proper language selector.
 *
 * @FormElement("product_availability_select")
 */
class ProductAvailabilitySelect extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#default_value' => ProductAvailabilityInterface::STATUS_AVAILABLE,
    ];
  }

}
