<?php

namespace Drupal\Tests\crm_core\Kernel;

use Drupal\crm_core\Form\SettingsForm;
use Drupal\KernelTests\ConfigFormTestBase;

/**
 * Test the settings form.
 *
 * @group user
 */
class SettingsFormTest extends ConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'crm_core'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->container->get('theme_installer')->install(['claro']);
    $this->form = SettingsForm::create($this->container);
    $this->values = [
      'crm_core_custom_theme' => [
        '#value' => 'claro',
        '#config_name' => 'crm_core.settings',
        '#config_key' => 'custom_theme',
      ],
    ];
  }

}
