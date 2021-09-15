<?php

namespace Drupal\arch_product\Plugin\DataType;

use Drupal\Core\TypedData\TypedData;
use Drupal\arch_product\Entity\ProductAvailability as ProductAvailabilityValue;

/**
 * Defines the 'product_availability' data type.
 *
 * @DataType(
 *   id = "product_availability",
 *   label = @Translation("Product availability", context = "arch_product"),
 *   description = @Translation("A product availability object.", context = "arch_product")
 * )
 */
class ProductAvailability extends TypedData {

  /**
   * The id of the product_availability.
   *
   * @var string
   */
  protected $id;

  /**
   * Availability.
   *
   * @var \Drupal\arch_product\Entity\ProductAvailabilityInterface
   */
  protected $availability;

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if (!isset($this->availability) && $this->id) {
      $this->availability = new ProductAvailabilityValue([
        'id' => $this->id,
      ]);
    }
    return $this->availability;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    // Support passing product_availability objects.
    if (is_object($value)) {
      $this->id = $value->getId();
      $this->availability = $value;
    }
    elseif (isset($value) && !is_scalar($value)) {
      throw new \InvalidArgumentException('Value is no valid product_availability or product_availability object.');
    }
    else {
      $this->id = $value;
      $this->availability = NULL;
    }
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getString() {
    $product_availability = $this->getValue();
    return $product_availability ? $product_availability->getName() : '';
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    if (isset($this->id)) {
      return $this->id;
    }
    elseif (isset($this->availability)) {
      return $this->availability->getId();
    }
  }

}
