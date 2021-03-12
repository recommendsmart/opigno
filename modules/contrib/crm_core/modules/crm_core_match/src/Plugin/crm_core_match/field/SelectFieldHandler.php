<?php

namespace Drupal\crm_core_match\Plugin\crm_core_match\field;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class for handling select fields.
 */
class SelectFieldHandler extends FieldHandlerBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getOperators($property = 'value') {
    return [
      'equals' => $this->t('Equals'),
    ];
  }

}
