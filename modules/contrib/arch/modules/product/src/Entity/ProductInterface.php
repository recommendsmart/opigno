<?php

namespace Drupal\arch_product\Entity;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Product entity interface.
 *
 * @package Drupal\arch_product\Entity
 */
interface ProductInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, RevisionLogInterface, EntityPublishedInterface {

  /**
   * Denotes that the product is not published.
   */
  const NOT_PUBLISHED = 0;

  /**
   * Denotes that the product is published.
   */
  const PUBLISHED = 1;

  /**
   * Denotes that the product is not promoted to the front page.
   */
  const NOT_PROMOTED = 0;

  /**
   * Denotes that the product is promoted to the front page.
   */
  const PROMOTED = 1;

  /**
   * Denotes that the product is not sticky at the top of the page.
   */
  const NOT_STICKY = 0;

  /**
   * Denotes that the product is sticky at the top of the page.
   */
  const STICKY = 1;

  /**
   * Gets the product type.
   *
   * @return string
   *   The product type.
   */
  public function getType();

  /**
   * Gets the product title.
   *
   * @return string
   *   Title of the product.
   */
  public function getTitle();

  /**
   * Sets the product title.
   *
   * @param string $title
   *   The product title.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface
   *   The called product entity.
   */
  public function setTitle($title);

  /**
   * Gets the product SKU.
   *
   * @return string
   *   SKU of the product.
   */
  public function getSku();

  /**
   * Sets the product SKU.
   *
   * @param string $sku
   *   The product SKU.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface
   *   The called product entity.
   */
  public function setSku($sku);

  /**
   * Gets the product creation timestamp.
   *
   * @return int
   *   Creation timestamp of the product.
   */
  public function getCreatedTime();

  /**
   * Sets the product creation timestamp.
   *
   * @param int $timestamp
   *   The product creation timestamp.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface
   *   The called product entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Get availability value.
   *
   * @return string
   *   Availability value.
   */
  public function getAvailability();

  /**
   * Sets availability value.
   *
   * @param string $availability
   *   Availability status.
   *
   * @return $this
   */
  public function setAvailability($availability);

  /**
   * Returns the product promotion status.
   *
   * @return bool
   *   TRUE if the product is promoted.
   */
  public function isPromoted();

  /**
   * Sets the product promoted status.
   *
   * @param bool $promoted
   *   TRUE to set this product to promoted, FALSE to set it to not promoted.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface
   *   The called product entity.
   */
  public function setPromoted($promoted);

  /**
   * Returns the product sticky status.
   *
   * @return bool
   *   TRUE if the product is sticky.
   */
  public function isSticky();

  /**
   * Sets the product sticky status.
   *
   * @param bool $sticky
   *   TRUE to set this product to sticky, FALSE to set it to not sticky.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface
   *   The called product entity.
   */
  public function setSticky($sticky);

  /**
   * Gets the product revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the product revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\arch_product\Entity\ProductInterface
   *   The called product entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Get list of prices.
   *
   * @return array
   *   List of prices.
   */
  public function getPrices();

  /**
   * Get list of prices available for given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User.
   *
   * @return array
   *   List of available prices.
   */
  public function getAvailablePrices(AccountInterface $account = NULL);

  /**
   * Get active price.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User.
   *
   * @return \Drupal\arch_price\Price\PriceInterface
   *   Active price.
   */
  public function getActivePrice(AccountInterface $account = NULL);

  /**
   * Check if product has any available price for given customer.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Customer.
   *
   * @return bool
   *   Return FALSE if has no available price.
   */
  public function hasPrice(AccountInterface $account = NULL);

  /**
   * Check if product is available for sell.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Customer.
   *
   * @return bool
   *   Return FALSE if has no available for sell.
   */
  public function availableForSell(AccountInterface $account = NULL);

}
