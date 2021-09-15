<?php

namespace Drupal\arch_order\Services;

/**
 * Order status service interface.
 *
 * @package Drupal\arch_order\Services
 */
interface OrderStatusServiceInterface {

  const ALL = 1;
  const LOCKED = 2;
  const UNLOCKED = 3;

  /**
   * Gets the default order status.
   *
   * @return \Drupal\arch_order\Entity\OrderStatusInterface
   *   Order status entity.
   */
  public function getDefaultOrderStatus();

  /**
   * Gets the order statuses available in the system according to the parameter.
   *
   * @param int $locked
   *   Filter to the locked status.
   *
   * @return \Drupal\arch_order\Services\OrderStatusServiceInterface[]
   *   Array of Order statuses.
   */
  public function getOrderStatuses($locked = self::ALL);

  /**
   * Load order status by its order status code.
   *
   * @param string $orderStatus
   *   Order status code.
   *
   * @return mixed
   *   Returns with an Order Status entity if found, NULL otherwise.
   */
  public function load($orderStatus);

}
