<?php

namespace Drupal\arch_product\Plugin\Action;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Field\FieldUpdateActionBase;

/**
 * Demotes a product.
 *
 * @Action(
 *   id = "product_unpromote_action",
 *   label = @Translation("Demote selected product from front page", context = "arch_product"),
 *   type = "product"
 * )
 */
class DemoteProduct extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['promote' => ProductInterface::NOT_PROMOTED];
  }

}
