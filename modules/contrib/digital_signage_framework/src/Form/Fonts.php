<?php

namespace Drupal\digital_signage_framework\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Serialization\Yaml;

/**
 * Configure Digital Signage fonts for this site.
 */
class Fonts extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'digital_signage_framework_settings_fonts';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['digital_signage_framework.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $fonts = Yaml::decode($this->config('digital_signage_framework.settings')->get('fonts')) ?? [];
    foreach ($fonts as $key => $font) {
      $form['font_' . $key] = [
        '#type' => 'checkbox',
        '#title' => $this->t('@family (@weight|@style|@stretch)', [
          '@family' => $font['family'],
          '@weight' => $font['weight'],
          '@style' => $font['style'],
          '@stretch' => $font['stretch'],
        ]),
        '#description' => Link::createFromRoute('Edit', 'digital_signage_framework.settings_fonts_edit', ['key' => $key]),
        '#default_value' => $font['enabled'],
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fonts = Yaml::decode($this->config('digital_signage_framework.settings')->get('fonts')) ?? [];
    foreach ($fonts as $key => $font) {
      $fonts[$key]['enabled'] = (bool) $form_state->getValue('font_' . $key);
    }
    $this->config('digital_signage_framework.settings')
      ->set('fonts', Yaml::encode($fonts))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
