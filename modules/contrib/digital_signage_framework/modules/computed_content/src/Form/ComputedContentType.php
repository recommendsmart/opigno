<?php

namespace Drupal\digital_signage_computed_content\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for computed content type forms.
 */
class ComputedContentType extends BundleEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity_type = $this->entity;
    if ($this->operation === 'add') {
      $form['#title'] = $this->t('Add computed content type');
    }
    else {
      $form['#title'] = $this->t(
        'Edit %label computed content type',
        ['%label' => $entity_type->label()]
      );
    }

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $entity_type->label(),
      '#description' => $this->t('The human-readable name of this computed content type.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_type->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => ['Drupal\digital_signage_computed_content\Entity\ComputedContentType', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this computed content type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save computed content type');
    $actions['delete']['#value'] = $this->t('Delete computed content type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity_type = $this->entity;

    $entity_type->set('id', trim($entity_type->id()));
    $entity_type->set('label', trim($entity_type->label()));

    $status = $entity_type->save();

    $t_args = ['%name' => $entity_type->label()];
    if ($status === SAVED_UPDATED) {
      $message = $this->t('The computed content type %name has been updated.', $t_args);
    }
    elseif ($status === SAVED_NEW) {
      $message = $this->t('The computed content type %name has been added.', $t_args);
    }
    else {
      $message = FALSE;
    }
    if ($message) {
      $this->messenger()->addStatus($message);
    }

    $form_state->setRedirectUrl($entity_type->toUrl('collection'));
  }

}
