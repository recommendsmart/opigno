<?php

namespace Drupal\arch_product_group\Plugin\Field\FieldType;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Is group parent item list.
 *
 * @package Drupal\arch_product_group\Plugin\Field\FieldType
 */
class IsGroupParentItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Group handler.
   *
   * @var \Drupal\arch_product_group\GroupHandlerInterface
   */
  protected $groupHandler;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $value = $this->getComputedValue($this->getEntity());
    $this->list = [
      $this->createItem(0, $value),
    ];
  }

  /**
   * Get computed value for entity.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $entity
   *   Product entity.
   *
   * @return bool
   *   Value to store.
   */
  protected function getComputedValue(ProductInterface $entity) {
    if (!$this->getProductGroupHandler()->isPartOfGroup($entity)) {
      return TRUE;
    }

    return $this->getProductGroupHandler()->isGroupParent($entity);
  }

  /**
   * Get group handler service.
   *
   * @return \Drupal\arch_product_group\GroupHandlerInterface
   *   Group handler.
   */
  protected function getProductGroupHandler() {
    if (!isset($this->groupHandler)) {
      // @codingStandardsIgnoreStart
      $this->groupHandler = \Drupal::service('product_group.handler');
      // @codingStandardsIgnoreEnd
    }

    return $this->groupHandler;
  }

}
