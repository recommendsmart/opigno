<?php

namespace Drupal\react_calendar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'react_calendar.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'react_calendar_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('react_calendar.settings');
    $calendarViews = [
      'month' => $this->t('Month'),
      'week' => $this->t('Week'),
      'day' => $this->t('Day'),
      'agenda' => $this->t('Agenda'),
    ];
    $form['default_view'] = [
      '#type' => 'select',
      '#title' => $this->t('Default view'),
      '#description' => $this->t('Calendar view that will be displayed by default.'),
      '#options' => $calendarViews,
      '#default_value' => $config->get('default_view'),
    ];
    $languageManager = \Drupal::languageManager();
    if ($languageManager->isMultilingual()) {
      $form['language_prefix'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Language prefix'),
        '#description' => $this->t('Get translated content from JSON API depending on the interface language.'),
        '#default_value' => $config->get('language_prefix'),
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // If the multilingual environment is disabled
    // make sure that the jsonapi_language_prefix is set back to false.
    $languageManager = \Drupal::languageManager();
    $languagePrefix = $form_state->getValue('language_prefix');
    if (!$languageManager->isMultilingual()) {
      $languagePrefix = FALSE;
    }
    $this->config('react_calendar.settings')
      ->set('default_view', $form_state->getValue('default_view'))
      ->set('language_prefix', $languagePrefix)
      ->save();
  }

}
