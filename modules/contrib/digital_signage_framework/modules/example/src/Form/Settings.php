<?php

namespace Drupal\digital_signage_example\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure example settings for this site.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'digital_signage_example_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['digital_signage_example.settings'];
  }

}
