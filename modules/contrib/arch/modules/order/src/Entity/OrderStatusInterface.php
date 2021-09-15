<?php

namespace Drupal\arch_order\Entity;

/**
 * Order status interface.
 *
 * @package Drupal\arch_order\Entity
 */
interface OrderStatusInterface {

  /**
   * Gets the description of the order status.
   *
   * @return string
   *   The description of the order status.
   */
  public function getDescription();

  /**
   * Gets the default status of this order status.
   *
   * @return bool
   *   Gets the default status of this order status.
   */
  public function getIsDefault();

  /**
   * Gets the weight of this order status.
   *
   * @return bool
   *   Gets the weight of this order status.
   */
  public function getWeight();

  /**
   * Gets the locked status of this order status.
   *
   * @return bool
   *   Gets the locked status of this order status.
   */
  public function isLocked();

  /**
   * Gets the name of the order status.
   *
   * @return string
   *   The human-readable name of the order status.
   */
  public function getLabel();

  /**
   * Gets the ID (order status code in ISO 4217 format).
   *
   * @return string
   *   The order status code.
   */
  public function getId();

}
