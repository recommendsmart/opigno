<?php

namespace Drupal\digital_signage_framework\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Push schedule to devices.
 */
class SchedulePush extends ActionBase {

  protected function id() {
    return 'digital_signage_schedule_push';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Push schedule to selected devices');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Push schedule');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['force'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force update'),
      '#default_value' => TRUE,
    ];
    $form['debugmode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug mode'),
      '#default_value' => FALSE,
    ];
    $form['reloadassets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reload assets (CSS, JS, fonts) on each schedule restart'),
      '#default_value' => FALSE,
    ];
    $form['reloadcontent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reload content on each schedule restart'),
      '#default_value' => FALSE,
    ];

    $form['single_entity'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Development mode'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $entities = $this->queryService->allEntitiesForSelect($this->devices);
    $form['single_entity']['singleslidemode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Single slide only'),
      '#default_value' => FALSE,
    ];
    $form['single_entity']['single_entity'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity'),
      '#default_value' => '',
      '#options' => $entities,
      '#states' => [
        'visible' => [
          ':input[name="singleslidemode"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    if ($form_state->getValue('confirm')) {
      $entity_type = NULL;
      $entity_id = NULL;
      if ($form_state->getValue('singleslidemode')) {
        [$entity_type, $entity_id] = explode('/', $form_state->getValue('single_entity'));
      }
      foreach ($this->devices as $device) {
        $this->scheduleManager->pushSchedules(
          $device->id(),
          $form_state->getValue('force'),
          $form_state->getValue('debugmode'),
          $form_state->getValue('reloadassets'),
          $form_state->getValue('reloadcontent'),
          $entity_type,
          $entity_id
        );
      }
    }
  }

}
