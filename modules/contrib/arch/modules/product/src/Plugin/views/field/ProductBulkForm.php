<?php

namespace Drupal\arch_product\Plugin\views\field;

use Drupal\views\Plugin\views\field\BulkForm;

/**
 * Defines a product operations bulk form element.
 *
 * @ViewsField("product_bulk_form")
 */
class ProductBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No product selected.', [], ['context' => 'arch_product']);
  }

}
