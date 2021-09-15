<?php

namespace Drupal\arch_downloadable_product;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\file\FileInterface;
use Drupal\user\UserInterface;

/**
 * Download URL builder interface.
 *
 * @package Drupal\arch_downloadable_product
 */
interface DownloadUrlBuilderInterface {

  /**
   * Get token for URL.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   * @param \Drupal\file\FileInterface $file
   *   File instance to download.
   * @param \Drupal\user\UserInterface $account
   *   Customer account.
   *
   * @return string
   *   Token.
   */
  public function getToken(ProductInterface $product, FileInterface $file, UserInterface $account);

  /**
   * Get download url.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Product entity.
   * @param \Drupal\file\FileInterface $file
   *   Purchased file.
   * @param \Drupal\user\UserInterface $account
   *   Customer account.
   *
   * @return \Drupal\Core\Url
   *   Generated URL.
   */
  public function getDownloadUrl(ProductInterface $product, FileInterface $file, UserInterface $account);

}
