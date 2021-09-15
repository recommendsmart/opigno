<?php

namespace Drupal\arch_price\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Provides a deletion confirmation form for VAT category.
 *
 * @internal
 */
class VatCategoryDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vat_category_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the VAT category %title?', ['%title' => $this->entity->label()], ['context' => 'arch_price']);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting a VAT category will delete all the prices in it. This action cannot be undone.', [], ['context' => 'arch_price']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('Deleted VAT category %name.', ['%name' => $this->entity->label()], [], ['context' => 'arch_price']);
  }

}
