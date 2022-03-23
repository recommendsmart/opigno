<?php

namespace Drupal\eca_form\Plugin\Action;

use Drupal\eca_form\Event\FormBase;

/**
 * Disable a form field.
 *
 * @Action(
 *   id = "eca_form_field_disable",
 *   label = @Translation("Form field: disable"),
 *   type = "form"
 * )
 */
class FormFieldDisable extends FormFieldActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if ($this->event instanceof FormBase) {
      $form = $this->event->getForm();
      $form[$this->configuration['field_name']]['#disabled'] = TRUE;
      $this->event->setForm($form);
    }
  }

}
