<?php

namespace Drupal\arch_checkout\Element;

use Drupal\address\Element\Address;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an address form element for checkout form.
 *
 * @FormElement("checkout_address")
 */
class CheckoutAddress extends Address {

  /**
   * {@inheritdoc}
   */
  public static function processAddress(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $element = parent::processAddress($element, $form_state, $complete_form);

    if (!empty($element['#ajax'])) {
      $fields = ['country_code'];
      if (!empty($element['#ajax']['fields'])) {
        $fields = array_unique(array_merge($fields, $element['#ajax']['fields']));
        unset($element['#ajax']['fields']);
      }
      foreach ($element['#value'] as $field_name => $value) {
        if (empty($element[$field_name])) {
          continue;
        }

        if (
          !empty($fields)
          && !in_array($field_name, $fields)
          && $field_name != 'country_code'
        ) {
          continue;
        }

        $element[$field_name]['#ajax'] = $element['#ajax'];
      }
    }
    else {
      $element['country_code']['#ajax']['wrapper'] = $complete_form['#wrapper_id'];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function clearValues(array $element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!$triggering_element) {
      return $element;
    }

    if (!in_array($element['#name'], $triggering_element['#parents'])) {
      return $element;
    }

    return parent::clearValues($element, $form_state);
  }

}
