<?php

namespace Drupal\eca_form\Plugin\ECA\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\StringComparisonBase;
use Drupal\eca_form\Event\FormBase;

/**
 * Plugin implementation of the ECA condition for a form field value.
 *
 * @EcaCondition(
 *   id = "eca_form_field_value",
 *   label = "Form field value"
 * )
 */
class FormFieldValue extends StringComparisonBase {

  /**
   * {@inheritdoc}
   */
  protected function getLeftValue(): string {
    if ($this->event instanceof FormBase) {
      $formState = $this->event->getFormState();
      if ($formState->hasValue($this->configuration['field_name'])) {
        return $formState->getValue($this->configuration['field_name']);
      }
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRightValue(): string {
    return $this->configuration['field_value'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
        'field_name' => '',
        'field_value' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['field_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field name'),
      '#default_value' => $this->configuration['field_name'],
      '#weight' => -10,
    ];
    $form['field_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field value'),
      '#default_value' => $this->configuration['field_value'],
      '#weight' => -8,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['field_name'] = $form_state->getValue('field_name');
    $this->configuration['field_value'] = $form_state->getValue('field_value');
    parent::submitConfigurationForm($form, $form_state);
  }

}
