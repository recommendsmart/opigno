<?php

namespace Drupal\react_calendar\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Node type settings form.
 */
class NodeTypeSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'react_calendar_node_type_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node_type = NULL) {
    /** @var \Drupal\react_calendar\CalendarConfigurationInterface $reactCalendarConfiguration */
    $calendarConfiguration = \Drupal::service('react_calendar.config');
    $storage = [
      'node_type' => $node_type,
    ];
    $form_state->setStorage($storage);

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable React Calendar for this content type.'),
      '#default_value' => $calendarConfiguration->getEntityBundleSettings('enabled', 'node', $node_type),
    ];

    // @todo if empty date fields, provide hint to add a new field.
    $dateFields = $calendarConfiguration->getDateFields($node_type);
    $dateFieldOptions = [];
    foreach ($dateFields as $dateFieldKey => $dateFieldValue) {
      $dateFieldOptions[$dateFieldKey] = $dateFieldValue->label();
    }
    $form['date_field'] = [
      '#type' => 'select',
      '#title' => t('Date field'),
      '#description' => t('The field instance can be from the <em>Date</em> or <em>Date Range</em> type.'),
      '#options' => $dateFieldOptions,
      '#default_value' => $calendarConfiguration->getEntityBundleSettings('date_field', 'node', $node_type),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $storage = $form_state->getStorage();
    $node_type = $storage['node_type'];
    // Update React Calendar settings.
    /** @var \Drupal\react_calendar\CalendarConfigurationInterface $calendarConfiguration */
    $calendarConfiguration = \Drupal::service('react_calendar.config');
    // Empty configuration if set again to disabled.
    if (!$values['enabled']) {
      $settings = $calendarConfiguration->getEntityBundleSettingDefaults();
    }
    else {
      $settings = $calendarConfiguration->getEntityBundleSettings('all', 'node', $node_type);
      foreach ($calendarConfiguration->availableEntityBundleSettings() as $setting) {
        if (isset($values[$setting])) {
          $settings[$setting] = is_array($values[$setting]) ? array_keys(array_filter($values[$setting])) : $values[$setting];
        }
      }
    }
    $calendarConfiguration->setEntityBundleSettings($settings, 'node', $node_type);
    $messenger = \Drupal::messenger();
    $messenger->addMessage(t('Your changes have been saved.'));
  }

}
