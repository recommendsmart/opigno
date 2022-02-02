<?php

namespace Drupal\digital_signage_framework\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure digital_signage_schedule settings for this site.
 */
class ScheduleSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'digital_signage_schedule_settings';
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
    $form['duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Duration'),
      '#default_value' => $this->config('digital_signage_framework.settings')->get('schedule.duration'),
      '#min' => 1,
      '#required' => TRUE,
      '#field_suffix' => $this->t('seconds'),
    ];
    $form['offsets'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Offsets'),
    ];
    $form['offsets']['complex'] = [
      '#type' => 'number',
      '#title' => $this->t('Complex'),
      '#default_value' => $this->config('digital_signage_framework.settings')->get('schedule.offsets.complex'),
      '#min' => 2,
      '#required' => TRUE,
    ];
    $form['offsets']['critical'] = [
      '#type' => 'number',
      '#title' => $this->t('Critical'),
      '#default_value' => $this->config('digital_signage_framework.settings')->get('schedule.offsets.critical'),
      '#min' => 2,
      '#required' => TRUE,
    ];
    $form['priority_weight'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Weight factors'),
    ];
    $form['priority_weight']['high'] = [
      '#type' => 'number',
      '#title' => $this->t('High'),
      '#default_value' => $this->config('digital_signage_framework.settings')->get('schedule.priority_weight.high'),
      '#min' => 3,
      '#required' => TRUE,
    ];
    $form['priority_weight']['normal'] = [
      '#type' => 'number',
      '#title' => $this->t('Normal'),
      '#default_value' => $this->config('digital_signage_framework.settings')->get('schedule.priority_weight.normal'),
      '#min' => 2,
      '#required' => TRUE,
    ];
    $form['priority_weight']['low'] = [
      '#type' => 'number',
      '#title' => $this->t('Low'),
      '#default_value' => $this->config('digital_signage_framework.settings')->get('schedule.priority_weight.low'),
      '#min' => 1,
      '#required' => TRUE,
    ];
    $form['dynamic_content'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Dynamic content'),
    ];
    $form['dynamic_content']['refresh'] = [
      '#type' => 'number',
      '#title' => $this->t('Refresh interval'),
      '#default_value' => $this->config('digital_signage_framework.settings')->get('schedule.dynamic_content.refresh'),
      '#min' => 1,
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('digital_signage_framework.settings')
      ->set('schedule.duration', $form_state->getValue('duration'))
      ->set('schedule.offsets.complex', $form_state->getValue('complex'))
      ->set('schedule.offsets.critical', $form_state->getValue('critical'))
      ->set('schedule.priority_weight.high', $form_state->getValue('high'))
      ->set('schedule.priority_weight.normal', $form_state->getValue('normal'))
      ->set('schedule.priority_weight.low', $form_state->getValue('low'))
      ->set('schedule.dynamic_content.refresh', $form_state->getValue('refresh'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
