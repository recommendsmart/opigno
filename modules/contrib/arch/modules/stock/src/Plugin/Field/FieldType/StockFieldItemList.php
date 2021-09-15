<?php

namespace Drupal\arch_stock\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;

/**
 * Represents a configurable entity stock field.
 */
class StockFieldItemList extends FieldItemList {

  /**
   * Get stocks.
   *
   * @return \Drupal\arch_stock\Plugin\Field\FieldType\Stock[]
   *   Stock items.
   */
  public function getStockList() {
    /** @var \Drupal\arch_stock\Plugin\Field\FieldType\Stock[] $items */
    $items = $this->list;
    return $items;
  }

}
