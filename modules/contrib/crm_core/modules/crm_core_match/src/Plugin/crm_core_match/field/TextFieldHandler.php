<?php

namespace Drupal\crm_core_match\Plugin\crm_core_match\field;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class for evaluating text fields.
 *
 * @CrmCoreMatchFieldHandler (
 *   id = "text"
 * )
 */
class TextFieldHandler extends FieldHandlerBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getOperators($property = 'value') {
    return [
      '=' => $this->t('Equals'),
      'STARTS_WITH' => $this->t('Starts with'),
      'ENDS_WITH' => $this->t('Ends with'),
      'CONTAINS' => $this->t('Contains'),
    ];
  }

}
