<?php

namespace Drupal\arch_stock\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Provides a deletion confirmation form for warehouse.
 *
 * @internal
 */
class WarehouseDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'warehouse_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
      'Are you sure you want to delete the warehouse %name?',
      ['%name' => $this->entity->label()],
      ['context' => 'arch_stock']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t(
      'Deleting a warehouse will delete all the stocks in it. This action cannot be undone.',
      [],
      ['context' => 'arch_stock']
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t(
      'Deleted warehouse %name.',
      ['%name' => $this->entity->label()],
      ['context' => 'arch_stock']
    );
  }

}
