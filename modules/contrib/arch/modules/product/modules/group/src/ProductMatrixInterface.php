<?php

namespace Drupal\arch_product_group;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Product matrix interface.
 *
 * @package Drupal\arch_product_group
 */
interface ProductMatrixInterface {

  /**
   * Get product matrix.
   *
   * @param string[] $fields
   *   Field names.
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product matrix.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   Account.
   *
   * @return array
   *   Matrix info.
   */
  public function getFieldValueMatrix(array $fields, ProductInterface $product, AccountInterface $account = NULL);

  /**
   * Get field value of given product.
   *
   * @param string $field_name
   *   Field name.
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   Account.
   *
   * @return mixed
   *   Value.
   */
  public function getFieldValue($field_name, ProductInterface $product, AccountInterface $account = NULL);

}
