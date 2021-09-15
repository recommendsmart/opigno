<?php

namespace Drupal\arch_product\Entity;

/**
 * Product availability interface.
 *
 * @package Drupal\arch_product\Entity
 */
interface ProductAvailabilityInterface {

  const STATUS_AVAILABLE = 'available';
  const STATUS_NOT_AVAILABLE = 'not_available';
  const STATUS_PREORDER = 'preorder';

  /**
   * Gets the name of the status.
   *
   * @return string
   *   The human-readable name of the availability.
   */
  public function getName();

  /**
   * Gets the ID (status code).
   *
   * @return string
   *   The status code.
   */
  public function getId();

}
