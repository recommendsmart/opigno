<?php

namespace Drupal\typed_telephone\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'typed_telephone_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "typed_telephone_default_formatter",
 *   label = @Translation("Typed telephone plain"),
 *   field_types = {
 *     "typed_telephone"
 *   }
 * )
 */
class TypedTelephoneFormatterType extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'concatenated' => 1,
      'separator' => '-'
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'concatenated' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Concatenated'),
        '#description' => $this->t('Whether to concatenate type and number into a single string. Otherwise, two span tags will be generated.'),
        '#default_value' => $this->getSetting('concatenated'),
      ],
      'separator' => [
        '#type' => 'textfield',
        '#title' => t('Type and telephone separator'),
        '#default_value' => $this->getSetting('separator'),
        '#description' => t('The glue string to place between type and number.'),
      ]
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Concatenated: @value', ['@value'=>(bool) $this->getSetting('concatenated')?'Yes':'No']);
    $summary[] = $this->t('Glue string: @value', ['@value'=>$this->getSetting('separator')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    $config_helper = \Drupal::service('typed_telephone.confighelper');

    $type = $config_helper->getLabelFromShortname($item->get('teltype')->getValue());
    $number = $item->get('value')->getValue();

    return [
      '#theme' => 'typed_telephone_plain',
      '#type' => $type,
      '#number' => $number,
      '#concatenated' => $this->getSetting('concatenated'),
      '#glue' => $this->getSetting('separator')
    ];
  }

}
