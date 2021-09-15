<?php

namespace Drupal\arch_product\Plugin\Action;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Field\FieldUpdateActionBase;

/**
 * Makes a product not sticky.
 *
 * @Action(
 *   id = "product_make_unsticky_action",
 *   label = @Translation("Make selected product not sticky", context = "arch_product"),
 *   type = "product"
 * )
 */
class UnstickyProduct extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['sticky' => ProductInterface::NOT_STICKY];
  }

}
