<?php

namespace Drupal\grouped_checkboxes\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;

/**
 * Defines a widget for grouping checkboxes by their parent group.
 *
 * @FieldWidget(
 *   id = "grouped_checkboxes",
 *   label = @Translation("Grouped check-boxes/radio buttons"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class GroupedCheckboxes extends OptionsButtonsWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $build = parent::formElement($items, $delta, $element, $form, $form_state);
    $build['#process'][] = [self::class, 'processCheckboxes'];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function supportsGroups() {
    return TRUE;
  }

  /**
   * Processes a checkboxes form element.
   *
   * Take the logic from
   * \Drupal\Core\Render\Element\Checkboxes::processCheckboxes but apply groups
   * as details elements.
   *
   * @see \Drupal\Core\Render\Element\Checkboxes::processCheckboxes
   */
  public static function processCheckboxes(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = is_array($element['#value']) ? $element['#value'] : [];
    $element['#tree'] = TRUE;
    if (count($element['#options']) > 0) {
      if (!isset($element['#default_value']) || $element['#default_value'] == 0) {
        $element['#default_value'] = [];
      }
      $weight = 0;
      foreach ($element['#options'] as $label => $choices) {
        $parent = Html::cleanCssIdentifier($label);
        $element[$parent] = [
          '#type' => 'details',
          '#title' => $label,
        ];
        foreach ($choices as $key => $choice) {
          // Integer 0 is not a valid #return_value, so use '0' instead.
          if ($key === 0) {
            $key = '0';
          }
          // Maintain order of options as defined in #options, in case the
          // element defines custom option sub-elements, but does not define all
          // option sub-elements.
          $weight += 0.001;

          $element[$parent] += [$key => []];
          $checked = isset($value[$key]) ? $key : NULL;
          if ($checked) {
            $element[$parent]['#open'] = TRUE;
          }
          $element[$parent][$key] += [
            '#type' => 'checkbox',
            '#title' => $choice,
            '#return_value' => $key,
            '#parents' => array_merge($element['#parents'], [$key]),
            '#default_value' => $checked,
            '#attributes' => $element['#attributes'],
            '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
            // Errors should only be shown on the parent checkboxes element.
            '#error_no_message' => TRUE,
            '#weight' => $weight,
          ];
        }
      }
    }
    // Now that we've processed, collapse the values so that form validator
    // doesn't flag the selection as invalid.
    $element['#options'] = OptGroup::flattenOptions($element['#options']);
    return $element;
  }

}
