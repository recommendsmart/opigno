<?php

namespace Drupal\digital_signage_framework\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Push schedule to devices.
 */
class ScheduleConfig extends ActionBase {

  protected function id() {
    return 'digital_signage_schedule_config';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Push config to selected devices');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Push config');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['debugmode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug mode'),
      '#default_value' => FALSE,
    ];
    $form['reloadschedule'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reload schedule'),
      '#default_value' => FALSE,
    ];
    $form['reloadassets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reload assets (CSS, JS, fonts)'),
      '#default_value' => FALSE,
    ];
    $form['reloadcontent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reload content'),
      '#default_value' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    if ($form_state->getValue('confirm')) {
      $debugmode = $form_state->getValue('debugmode');
      $reloadschedule = $form_state->getValue('reloadschedule');
      $reloadassets = $form_state->getValue('reloadassets');
      $reloadcontent = $form_state->getValue('reloadcontent');
      foreach ($this->devices as $device) {
        $this->scheduleManager->pushConfiguration(
          $device->id(),
          $debugmode,
          $reloadschedule,
          $reloadassets,
          $reloadcontent
        );
      }
    }
  }

}
