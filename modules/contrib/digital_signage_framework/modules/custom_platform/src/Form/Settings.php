<?php

namespace Drupal\digital_signage_custom_platform\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Configure custom settings for this site.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'digital_signage_custom_platform_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['digital_signage_custom_platform.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['devices'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Devices'),
      '#default_value' => Yaml::encode($this->config('digital_signage_custom_platform.settings')->get('devices')),
      '#attributes' => ['data-yaml-editor' => 'true'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('digital_signage_custom_platform.settings')
      ->set('devices', Yaml::decode($form_state->getValue('devices')))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
