<?php

namespace Drupal\arch_order\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Defines a confirmation form for deleting a order status entity.
 *
 * @internal
 */
class OrderStatusDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting an order status might cause malfunction in commerce system if it is already in use. This action cannot be undone.', [], ['context' => 'arch_order_status']);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'order_status_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('The %order_status_label (%order_status) order status has been removed.', [
      '%order_status_label' => $this->entity->label(),
      '%order_status' => $this->entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function logDeletionMessage() {
    $this->logger('Order Status')
      ->notice('The %order_status_label (%order_status) order status has been removed.', [
        '%order_status_label' => $this->entity->label(),
        '%order_status' => $this->entity->id(),
      ]);
  }

}
