<?php

namespace Drupal\eca_form\Plugin\Action;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca_form\Event\FormBase;

/**
 * Base class for form field validation actions.
 */
abstract class FormFieldValidateActionBase extends FormFieldActionBase  {

  /**
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   */
  protected function setError(TranslatableMarkup $message): void {
    if ($this->event instanceof FormBase) {
      $this->event->getFormState()->setErrorByName($this->configuration['field_name'], $message);
    }
  }

}
