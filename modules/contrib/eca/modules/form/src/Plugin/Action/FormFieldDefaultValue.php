<?php

namespace Drupal\eca_form\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Set default value of a form field.
 *
 * @Action(
 *   id = "eca_form_field_default_value",
 *   label = @Translation("Form field: set default value"),
 *   description = @Translation("Prepopulates a default value in the form."),
 *   type = "form"
 * )
 */
class FormFieldDefaultValue extends FormFieldActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if ($element = &$this->getTargetElement()) {
      $element = &$this->jumpToFirstFieldChild($element);
      $value = $this->tokenServices->replaceClear($this->configuration['value']);
      $this->filterFormFieldValue($value);
      $element['#default_value'] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'value' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#description' => $this->t('The default value to prepopulate. Supports tokens.'),
      '#default_value' => $this->configuration['value'],
      '#weight' => -49,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['value'] = $form_state->getValue('value');
    parent::submitConfigurationForm($form, $form_state);
  }

}
