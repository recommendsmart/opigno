<?php

namespace Drupal\arch_product\Plugin\Action;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Field\FieldUpdateActionBase;

/**
 * Makes a product sticky.
 *
 * @Action(
 *   id = "product_make_sticky_action",
 *   label = @Translation("Make selected product sticky", context = "arch_product"),
 *   type = "product"
 * )
 */
class StickyProduct extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['sticky' => ProductInterface::STICKY];
  }

}
