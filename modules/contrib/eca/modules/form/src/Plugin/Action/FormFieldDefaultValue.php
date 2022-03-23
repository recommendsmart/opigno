<?php

namespace Drupal\eca_form\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca_form\Event\FormBase;

/**
 * Set default value of a form field.
 *
 * @Action(
 *   id = "eca_form_field_default_value",
 *   label = @Translation("Form field: set default value"),
 *   type = "form"
 * )
 */
class FormFieldDefaultValue extends FormFieldActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if ($this->event instanceof FormBase) {
      $form_state = $this->event->getFormState();
      if (!$form_state->isProcessingInput()) {
        $form = $this->event->getForm();
        $form[$this->configuration['field_name']]['#default_value'] = $this->tokenServices->replaceClear($this->configuration['value']);
        $this->event->setForm($form);
      }
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
    parent::buildConfigurationForm($form, $form_state);
    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#default_value' => $this->configuration['value'],
      '#weight' => -9,
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
