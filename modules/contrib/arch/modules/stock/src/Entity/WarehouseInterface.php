<?php

namespace Drupal\arch_stock\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a warehouse entity.
 */
interface WarehouseInterface extends ConfigEntityInterface {

  /**
   * Returns the name of the warehouse.
   *
   * @return string
   *   The name of the warehouse.
   */
  public function getName();

  /**
   * Returns the warehouse description.
   *
   * @return string
   *   The warehouse description.
   */
  public function getDescription();

  /**
   * Determines if this warehouse entity is locked.
   *
   * @return bool
   *   TRUE if the entity is locked, FALSE otherwise.
   */
  public function isLocked();

  /**
   * Check if warehouse allowing overbooking.
   *
   * @return bool
   *   Return TRUE of overbooking is allowed for this warehouse.
   */
  public function allowNegative();

  /**
   * Get status value to change product Availability after overbooking.
   *
   * @return string|null
   *   Availability value.
   */
  public function getOverBookedAvailability();

}
