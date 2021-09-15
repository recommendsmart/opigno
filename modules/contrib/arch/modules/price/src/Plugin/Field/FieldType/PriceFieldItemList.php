<?php

namespace Drupal\arch_price\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;

/**
 * Represents a configurable entity price field.
 */
class PriceFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    return $constraints;
  }

  /**
   * Get prices.
   *
   * @return \Drupal\arch_price\Plugin\Field\FieldType\PriceItem[]
   *   Price items.
   */
  public function getPriceList() {
    /** @var \Drupal\arch_price\Plugin\Field\FieldType\PriceItem[] $items */
    $items = $this->list;
    return $items;
  }

}
