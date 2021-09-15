<?php

namespace Drupal\arch_product\Form;

use Drupal\Core\Entity\Form\DeleteMultipleForm as EntityDeleteMultipleForm;
use Drupal\Core\Url;

/**
 * Provides a product deletion confirmation form.
 *
 * @internal
 */
class DeleteMultiple extends EntityDeleteMultipleForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('arch.dashboard');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletedMessage($count) {
    return $this->formatPlural(
      $count,
      'Deleted @count product item.',
      'Deleted @count product items.'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getInaccessibleMessage($count) {
    return $this->formatPlural(
      $count,
      "@count product item has not been deleted because you do not have the necessary permissions.",
      "@count product items have not been deleted because you do not have the necessary permissions."
    );
  }

}
