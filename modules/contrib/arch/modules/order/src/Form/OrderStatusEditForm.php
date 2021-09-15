<?php

namespace Drupal\arch_order\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Controller for order status edit forms.
 *
 * @internal
 */
class OrderStatusEditForm extends OrderStatusBaseForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // @todo Remove in favour of base method.
    return 'order_status_admin_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $this->commonForm($form);
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save order status', [], ['context' => 'arch_order_status']),
      '#validate' => ['::validateCommon'],
      '#submit' => ['::submitForm', '::save'],
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    $this->logger('Order Status')
      ->notice('The %order_status_label (%order_status) language has been updated.', [
        '%order_status_label' => $this->entity->label(),
        '%order_status' => $this->entity->id(),
      ]);
  }

}
