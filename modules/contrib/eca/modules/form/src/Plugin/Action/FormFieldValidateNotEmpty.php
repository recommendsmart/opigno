<?php

namespace Drupal\eca_form\Plugin\Action;

use Drupal\eca_form\Event\FormBase;

/**
 * Disable a form field.
 *
 * @Action(
 *   id = "eca_form_field_validate_not_empty",
 *   label = @Translation("Validate form field: should not be empty"),
 *   type = "form"
 * )
 */
class FormFieldValidateNotEmpty extends FormFieldValidateActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if ($this->event instanceof FormBase) {
      $value = $this->event->getFormState()->getValue($this->configuration['field_name']);
      if (is_array($value) && isset($value[0]['value'])) {
        $value = $value[0]['value'];
      }
      if (empty($value)) {
        $this->setError($this->t('This field can not be empty'));
      }
    }
  }

}
