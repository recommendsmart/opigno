<?php

namespace Drupal\dimension\Plugin\Field;

trait LengthTrait {

  /**
   * {@inheritdoc}
   */
  public static function fields(): array {
    return [
      'length' => t('Length'),
    ];
  }

}
