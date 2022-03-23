<?php

namespace Drupal\eca\Service;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eca\Plugin\OptionsInterface;

/**
 * Trait for ECA modeller, condition and action services.
 */
trait ServiceTrait {

  use StringTranslationTrait;

  /**
   * Helper function to sort plugins by their label.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface[] $plugins
   *   The list of plugin to be sorted.
   */
  public function sortPlugins(array &$plugins): void {
    usort($plugins, static function($p1, $p2) {
      $l1 = (string) $p1->getPluginDefinition()['label'];
      $l2 = (string) $p2->getPluginDefinition()['label'];
      if ($l1 < $l2) {
        return -1;
      }
      if ($l1 > $l2) {
        return 1;
      }
      return 0;
    });
  }

  /**
   * Helper function to sort fields by their weight.
   *
   * @param array $fields
   *   The list of fields to be sorted.
   */
  public function sortFields(array &$fields): void {
    usort($fields, static function($f1, $f2) {
      $l1 = (int) $f1['weight'];
      $l2 = (int) $f2['weight'];
      if ($l1 < $l2) {
        return -1;
      }
      if ($l1 > $l2) {
        return 1;
      }
      return 0;
    });
  }

  /**
   * Helper function to prepare a config field for actions and conditions.
   *
   * @param array $fields
   *   The array to which the fields should be added.
   * @param array $config
   *   The array received from defaultConfiguration of actions and conditions.
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $plugin
   *   The plugin to which the field belong to.
   */
  protected function prepareConfigFields(array &$fields, array $config, PluginInspectionInterface $plugin): void {
    $form = [];
    if ($plugin instanceof PluginFormInterface) {
      $form_state = new FormState();
      $form = $plugin->buildConfigurationForm($form, $form_state);
    }
    foreach ($config as $key => $value) {
      $label = NULL;
      $description = NULL;
      $type = 'String';
      $weight = 0;
      if ($form) {
        if (isset($form[$key]['#title'])) {
          $label = (string) $form[$key]['#title'];
        }
        if (isset($form[$key]['#weight'])) {
          $weight = (string) $form[$key]['#weight'];
        }
        $description = $form[$key]['#description'] ?? NULL;
        if (isset($form[$key]['#type'])) {
          // @todo Map to more proper property types of bpmn-js.
          switch ($form[$key]['#type']) {

            case 'textarea':
              $type = 'Text';
              break;

            case 'select':
              $fields[] = $this->optionsField($key, $this->fieldLabel($label, $key), $weight, $description, $form[$key]['#options'], (string) $value);
              continue 2;

          }
        }
      }
      $label = $this->fieldLabel($label, $key);
      if (is_bool($value)) {
        $fields[] = $this->checkbox($key, $label, $weight, $value);
        continue;
      }
      if (is_array($value)) {
        $value = implode(',', $value);
      }
      elseif ($plugin instanceof OptionsInterface && $options = $plugin->getOptions($key)) {
        $fields[] = $this->optionsField($key, $label, $weight, $description, $options, (string) $value);
        continue;
      }
      $field = [
        'name' => $key,
        'label' => $label,
        'weight' => $weight,
        'type' => $type,
        'value' => $value,
      ];
      if ($description !== NULL) {
        $field['description'] = $description;
      }
      $fields[] = $field;
    }
    $this->sortFields($fields);
  }

  /**
   * Builds a field label from the key, if no label is given yet,
   *
   * @param string|null $label
   *   The given label or NULL, is none is available.
   * @param string $key
   *   The key of the field from which to build a label.
   *
   * @return string
   *   The built label for the field identified by key.
   */
  protected function fieldLabel(?string $label, string $key): string {
    if ($label === NULL) {
      $labelParts = explode('_', $key);
      $labelParts[0] = ucfirst($labelParts[0]);
      $label = implode(' ', $labelParts);
    }
    return $label;
  }

  /**
   * Prepares a field with options as a drop-down.
   *
   * @param string $name
   *   The field name.
   * @param string $label
   *   The field label.
   * @param int $weight
   *   The field weight for sorting.
   * @param string|null $description
   *   The optional field description.
   * @param array $options
   *   Key/value list of available options.
   * @param string $value
   *   The default value for the field.
   *
   * @return array
   *   Prepared option field.
   */
  protected function optionsField(string $name, string $label, int $weight, ?string $description, array $options, string $value): array {
    $choices = [];
    foreach ($options as $optionValue => $optionName) {
      $choices[] = [
        'name' => (string) $optionName,
        'value' => (string) $optionValue,
      ];
    }
    $field = [
      'name' => $name,
      'label' => $label,
      'weight' => $weight,
      'type' => 'Dropdown',
      'value' => $value,
      'extras' => [
        'choices' => $choices,
      ],
    ];
    if ($description !== NULL) {
      $field['description'] = $description;
    }
    return $field;
  }

  /**
   * Prepares a field as a checkbox.
   *
   * @param string $name
   *   The field name.
   * @param string $label
   *   The field label.
   * @param int $weight
   *   The field weight for sorting.
   * @param string $value
   *   The default value for the field.
   *
   * @return array
   *   Prepared checkbox field.
   */
  protected function checkbox(string $name, string $label, int $weight, string $value): array {
    return [
      'name' => $name,
      'label' => $label,
      'weight' => $weight,
      'type' => 'Dropdown',
      'value' => $value ? Conditions::OPTION_YES : Conditions::OPTION_NO,
      'extras' => [
        'choices' => [
          [
            'name' => 'no',
            'value' => Conditions::OPTION_NO,
          ],
          [
            'name' => 'yes',
            'value' => Conditions::OPTION_YES,
          ],
        ],
      ],
    ];
  }

}
