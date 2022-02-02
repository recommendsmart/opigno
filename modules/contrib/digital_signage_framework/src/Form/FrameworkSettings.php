<?php

namespace Drupal\digital_signage_framework\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Digital Signage settings for this site.
 */
class FrameworkSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'digital_signage_framework_settings';
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
    $form['cron_sync_devices'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sync devices during cron'),
      '#default_value' => $this->config('digital_signage_framework.settings')->get('cron_sync_devices'),
    ];

    $form['cron_create_schedules'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create schedules during cron'),
      '#default_value' => $this->config('digital_signage_framework.settings')->get('cron_create_schedules'),
    ];

    $form['hotfix_svg'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Apply hotfix for SVG files'),
      '#default_value' => $this->config('digital_signage_framework.settings')->get('hotfix_svg'),
      '#description' => $this->t('Some devices can not display SVG files with missing <?xml> prefix in them. This option allows to dynamically fix that.'),
    ];

    $form['plantuml_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PlantUML server'),
      '#default_value' => $this->config('digital_signage_framework.settings')->get('plantuml_url'),
    ];

    $form['css'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CSS files'),
      '#default_value' => $this->config('digital_signage_framework.settings')->get('css'),
    ];

    $form['http_header'] = [
      '#type' => 'textarea',
      '#title' => $this->t('HTTP request header'),
      '#default_value' => $this->config('digital_signage_framework.settings')->get('http_header'),
      '#attributes' => ['data-yaml-editor' => 'true'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('digital_signage_framework.settings')
      ->set('cron_sync_devices', $form_state->getValue('cron_sync_devices'))
      ->set('cron_create_schedules', $form_state->getValue('cron_create_schedules'))
      ->set('hotfix_svg', $form_state->getValue('hotfix_svg'))
      ->set('plantuml_url', $form_state->getValue('plantuml_url'))
      ->set('css', $form_state->getValue('css'))
      ->set('http_header', $form_state->getValue('http_header'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
