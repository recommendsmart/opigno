<?php

namespace Drupal\arch_downloadable_product;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;

/**
 * Product file access interface.
 *
 * @package Drupal\arch_downloadable_product
 */
interface ProductFileAccessInterface {

  /**
   * Check given account has access to file through given product.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product instance.
   * @param \Drupal\file\FileInterface $file
   *   File to check.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   Account.
   *
   * @return bool
   *   Return TRUE if customer can access to file.
   */
  public function check(ProductInterface $product, FileInterface $file, AccountInterface $user);

  /**
   * Check given account has access to file and also check token.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product instance.
   * @param \Drupal\file\FileInterface $file
   *   File to check.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   Account.
   * @param string $token_to_check
   *   Token.
   *
   * @return bool
   *   Return TRUE if customer can access to file.
   */
  public function checkWithToken(ProductInterface $product, FileInterface $file, AccountInterface $user, $token_to_check);

  /**
   * Check user with given UUID can access to file with UUID.
   *
   * @param int $product_id
   *   Product ID.
   * @param string $file_uuid
   *   File UUID.
   * @param string $user_uuid
   *   User UUID.
   *
   * @return bool
   *   Returns TRUE if customer can access to file.
   */
  public function checkByIds($product_id, $file_uuid, $user_uuid);

  /**
   * Check user with given UUID can access to file with UUID.
   *
   * @param int $product_id
   *   Product ID.
   * @param string $file_uuid
   *   File UUID.
   * @param string $user_uuid
   *   User UUID.
   * @param string $token_to_check
   *   Token.
   *
   * @return bool
   *   Returns TRUE if user can access and token matched.
   */
  public function checkByIdsWithToken($product_id, $file_uuid, $user_uuid, $token_to_check);

}
