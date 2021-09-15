<?php

namespace Drupal\arch_product_group;

use Drupal\arch_product\Entity\ProductInterface;

/**
 * Product group handler interface.
 *
 * @package Drupal\arch_product_group
 */
interface GroupHandlerInterface {

  /**
   * Check if given product is a part of a group.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   *
   * @return bool
   *   Returns TRUE if products group has at least 2 elements.
   */
  public function isPartOfGroup(ProductInterface $product);

  /**
   * Check if given product is a group parent.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   *
   * @return bool
   *   Returns TRUE if product is a parent of a group with at least 2 elements.
   */
  public function isGroupParent(ProductInterface $product);

  /**
   * Get ID if product group.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   *
   * @return int|bool
   *   If product is a part of a group returns the ID of it. Else returns FALSE.
   */
  public function getGroupId(ProductInterface $product);

  /**
   * Get parent product of the group of given product.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface|null
   *   If given product is part of a group returns the group parent product.
   *   Else returns NULL.
   */
  public function getGroupParent(ProductInterface $product);

  /**
   * Get member products of group of given product.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface[]
   *   Group members.
   */
  public function getGroupProducts(ProductInterface $product);

  /**
   * Create a new group.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface[] $products
   *   Group members.
   * @param int|null $group_id
   *   ID of new group. If empty first ID of list will be used.
   *
   * @return bool
   *   Return TRUE on success or FALSE on failure.
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createGroup(array $products, $group_id = NULL);

  /**
   * Remove product from given group.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   * @param int $group_id
   *   Group ID.
   *
   * @return bool
   *   Return TRUE on success or FALSE on failure.
   */
  public function removeFromGroup(ProductInterface $product, $group_id);

  /**
   * Leave group.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   *
   * @return bool
   *   Return TRUE on success or FALSE on failure.
   */
  public function leaveGroup(ProductInterface $product);

  /**
   * Add product to group.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   * @param int $group_id
   *   Group ID.
   *
   * @return bool
   *   Return TRUE on success or FALSE on failure.
   */
  public function addToGroup(ProductInterface $product, $group_id);

  /**
   * Remove every product from group.
   *
   * @param int $group_id
   *   Group ID.
   *
   * @return bool
   *   Return TRUE on success or FALSE on failure.
   */
  public function dismissGroup($group_id);

}
