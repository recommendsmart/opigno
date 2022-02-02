<?php

namespace Drupal\digital_signage_framework\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;

/**
 * Add or edit font for Digital Signage on this site.
 */
class Font extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'digital_signage_framework_settings_fonts_edit';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['digital_signage_framework.settings'];
  }

  private function defaultFont() {
    return [
      'enabled' => TRUE,
      'family' => '',
      'weight' => '',
      'style' => '',
      'stretch' => '',
      'urange' => '',
      'formats' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $key = -1): array {
    $fonts = Yaml::decode($this->config('digital_signage_framework.settings')->get('fonts')) ?? [];
    $font = $fonts[$key] ?? $this->defaultFont();

    $form['key'] = ['#type' => 'hidden', '#value' => $key];
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $font['enabled'],
    ];
    $form['family'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Family'),
      '#default_value' => $font['family'],
      '#required' => TRUE,
    ];
    $form['weight'] = [
      '#type' => 'select',
      '#title' => $this->t('Weight'),
      '#default_value' => $font['weight'],
      '#options' => [
        'normal' => $this->t('normal'),
        'bold' => $this->t('bold'),
        'bolder' => $this->t('bolder'),
        'light' => $this->t('light'),
        'lighter' => $this->t('lighter'),
        '100' => $this->t('100'),
        '200' => $this->t('200'),
        '300' => $this->t('300'),
        '400' => $this->t('400'),
        '500' => $this->t('500'),
        '600' => $this->t('500'),
        '700' => $this->t('700'),
        '800' => $this->t('800'),
        '900' => $this->t('900'),
        'initial' => $this->t('initial'),
        'inherit' => $this->t('inherit'),
      ],
    ];
    $form['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Style'),
      '#default_value' => $font['style'],
      '#options' => [
        'normal' => $this->t('normal'),
        'italic' => $this->t('italic'),
        'oblique' => $this->t('oblique'),
        'initial' => $this->t('initial'),
        'inherit' => $this->t('inherit'),
      ],
    ];
    $form['stretch'] = [
      '#type' => 'select',
      '#title' => $this->t('Stretch'),
      '#default_value' => $font['stretch'],
      '#options' => [
        'normal' => $this->t('normal'),
        'condensed' => $this->t('condensed'),
        'semi-condensed' => $this->t('semi-condensed'),
        'extra-condensed' => $this->t('extra-condensed'),
        'ultra-condensed' => $this->t('ultra-condensed'),
        'expanded' => $this->t('expanded'),
        'semi-expanded' => $this->t('semi-expanded'),
        'extra-expanded' => $this->t('extra-expanded'),
        'ultra-expanded' => $this->t('ultra-expanded'),
        'initial' => $this->t('initial'),
        'inherit' => $this->t('inherit'),
      ],
    ];
    $form['urange'] = [
      '#type' => 'select',
      '#title' => $this->t('Unicode range'),
      '#default_value' => $font['urange'],
      '#options' => [
        'U+0-10FFFF' => $this->t('U+0-10FFFF'),
      ],
    ];

    $form['formats'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Font files'),
      '#tree' => TRUE,
      '#description' => $this->t('Provide the URL from where to download each font.')
    ];
    foreach (['woff2', 'woff', 'eot', 'ttf', 'svg'] as $format) {
      $form['formats'][$format] = [
        '#type' => 'textfield',
        '#title' => $this->t('@format', ['@format' => $format]),
        '#default_value' => $font['formats'][$format] ?? '',
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $font = $form_state
      ->cleanValues()
      ->getValues();
    $key = (int) $font['key'];
    unset($font['key']);
    foreach ($font['formats'] as $format => $url) {
      if (empty($url)) {
        unset($font['formats'][$format]);
      }
    }
    $fonts = Yaml::decode($this->config('digital_signage_framework.settings')->get('fonts')) ?? [];
    if ($key < 0) {
      $fonts[] = $font;
    }
    else {
      $fonts[$key] = $font;
    }
    $this->config('digital_signage_framework.settings')
      ->set('fonts', Yaml::encode($fonts))
      ->save();
    $form_state->setRedirect('digital_signage_framework.settings_fonts');
    parent::submitForm($form, $form_state);
  }

}
