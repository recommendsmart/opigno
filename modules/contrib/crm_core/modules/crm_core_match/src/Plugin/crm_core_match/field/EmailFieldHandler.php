<?php

namespace Drupal\crm_core_match\Plugin\crm_core_match\field;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class for evaluating email fields.
 *
 * @CrmCoreMatchFieldHandler (
 *   id = "email"
 * )
 */
class EmailFieldHandler extends FieldHandlerBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getOperators($property = 'value') {
    return [
      '=' => $this->t('Equals'),
    ];
  }

}
