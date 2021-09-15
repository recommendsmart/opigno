<?php

namespace Drupal\arch_price\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Provides a deletion confirmation form for price type.
 *
 * @internal
 */
class PriceTypeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'price_type_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the price type %title?', ['%title' => $this->entity->label()], ['context' => 'arch_price']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting a price type will delete all the prices in it. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('Deleted price type %name.', ['%name' => $this->entity->label()], ['context' => 'arch_price']);
  }

}
