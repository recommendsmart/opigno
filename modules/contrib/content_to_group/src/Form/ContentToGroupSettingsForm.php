<?php

namespace Drupal\content_to_group\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\content_to_group\Util\ContentToGroupUtility;

class ContentToGroupSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'Content_to_group_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['content_to_group.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('content_to_group.settings');
    $contentToGroupUtility = new ContentToGroupUtility(\Drupal::entityTypeManager());
    $typeOptions = $contentToGroupUtility->getContentTypes();

    // Content types selection.
    $form['types'] = [
      '#type' => 'select',
      '#title' => $this->t('Select content types'),
      '#description' => $this->t('Select the content types to include.'),
      '#options' => !empty($typeOptions) ? $typeOptions : 'No content types',
      '#default_value' => $config->get('types'),
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->configFactory->getEditable('content_to_group.settings');
    $config->set('types', $form_state->getValue('types'));
    $config->save();

    parent::submitForm($form, $form_state);
  }


}
