<?php

namespace Drupal\arch_product\Plugin\Action;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Field\FieldUpdateActionBase;

/**
 * Promotes a product.
 *
 * @Action(
 *   id = "product_promote_action",
 *   label = @Translation("Promote selected product to front page", context = "arch_product"),
 *   type = "product"
 * )
 */
class PromoteProduct extends FieldUpdateActionBase {

  /**
   * {@inheritdoc}
   */
  protected function getFieldsToUpdate() {
    return ['promote' => ProductInterface::PROMOTED];
  }

}
