<?php

namespace Drupal\arch_product\Plugin\DataType;

use Drupal\Core\TypedData\DataReferenceBase;

/**
 * Defines the 'product_availability_reference' data type.
 *
 * This serves as 'product_availability' property of language field items and
 * gets its value set from the parent, i.e. ProductAvailabilityItem.
 *
 * @DataType(
 *   id = "product_availability_reference",
 *   label = @Translation("Product availability reference", context = "arch_product"),
 *   definition_class = "\Drupal\Core\TypedData\DataReferenceDefinition"
 * )
 */
class ProductAvailabilityReference extends DataReferenceBase {

  /**
   * {@inheritdoc}
   */
  public function getTargetIdentifier() {
    $availability = $this->getTarget();
    return isset($availability) ? $availability->id() : NULL;
  }

}
