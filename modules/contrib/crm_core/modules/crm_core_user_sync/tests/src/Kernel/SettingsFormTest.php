<?php

namespace Drupal\Tests\crm_core_user_sync\Kernel;

use Drupal\crm_core_user_sync\Form\SettingsForm;
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
  public static $modules = [
    'system',
    'crm_core',
    'user',
    'crm_core_contact',
    'crm_core_user_sync',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig('crm_core_user_sync');
    $this->form = SettingsForm::create($this->container);
    $this->values = [
      'auto_sync_user_create' => [
        '#value' => TRUE,
        '#config_name' => 'crm_core_user_sync.settings',
        '#config_key' => 'auto_sync_user_create',
      ],
      'auto_sync_user_relate' => [
        '#value' => TRUE,
        '#config_name' => 'crm_core_user_sync.settings',
        '#config_key' => 'auto_sync_user_relate',
      ],
      'contact_load' => [
        '#value' => TRUE,
        '#config_name' => 'crm_core_user_sync.settings',
        '#config_key' => 'contact_load',
      ],
      'contact_show' => [
        '#value' => TRUE,
        '#config_name' => 'crm_core_user_sync.settings',
        '#config_key' => 'contact_show',
      ],
    ];
  }

}
