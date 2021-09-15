<?php

namespace Drupal\arch_order\Plugin\DataType;

use Drupal\Core\TypedData\DataReferenceBase;

/**
 * Defines the 'order_status_reference' data type.
 *
 * This serves as 'order_status_reference' property of order status field items
 * and gets its value set from the parent, i.e. OrderStatusItem.
 *
 * @DataType(
 *   id = "order_status_reference",
 *   label = @Translation("Order status reference", context = "arch_order"),
 *   definition_class = "\Drupal\Core\TypedData\DataReferenceDefinition"
 * )
 */
class OrderStatusReference extends DataReferenceBase {

  /**
   * {@inheritdoc}
   */
  public function getTargetIdentifier() {
    $status = $this->getTarget();
    return isset($status) ? $status->id() : NULL;
  }

}
