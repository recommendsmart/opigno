<?php

namespace Drupal\arch_product\Entity\Storage;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for product entity storage classes.
 */
interface ProductStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of product revision IDs for a specific product.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   The product entity.
   *
   * @return int[]
   *   Product revision IDs (in ascending order).
   */
  public function revisionIds(ProductInterface $product);

  /**
   * Gets a list of revision IDs having a given user as product creator.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Product revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   The product entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ProductInterface $product);

  /**
   * Updates all products of one type to be of another type.
   *
   * @param string $old_type
   *   The current product type of the products.
   * @param string $new_type
   *   The new product type of the products.
   *
   * @return int
   *   The number of products whose product type field was modified.
   */
  public function updateType($old_type, $new_type);

  /**
   * Unsets the language for all products with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
