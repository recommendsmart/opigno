<?php

namespace Drupal\arch_order\Services;

use Drupal\arch_order\OrderAddressDataInterface;

/**
 * Order address service interface.
 *
 * @package Drupal\arch_order\Services
 */
interface OrderAddressServiceInterface {

  const TYPE_BILLING = 'billing';

  const TYPE_SHIPPING = 'shipping';

  const TABLE_ORDER_ADDRESS = 'arch_order_address';

  /**
   * Insert a type of address for an order.
   *
   * @param string $type
   *   One of a type of \Drupal\arch_order\OrderAddressServiceInterface class.
   * @param \Drupal\arch_order\OrderAddressDataInterface $data
   *   Address data.
   *
   * @return bool
   *   Return FALSE on failure.
   *
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   * @throws \Drupal\Core\Database\IntegrityConstraintViolationException
   * @throws \InvalidArgumentException
   * @throws \Exception
   */
  public function insertAddress($type, OrderAddressDataInterface $data);

  /**
   * Update stored data of address for an order.
   *
   * @param \Drupal\arch_order\OrderAddressDataInterface $data
   *   Address data.
   *
   * @return bool
   *   Return FALSE on failure.
   *
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   * @throws \Drupal\Core\Database\IntegrityConstraintViolationException
   * @throws \InvalidArgumentException
   * @throws \Exception
   */
  public function updateAddress(OrderAddressDataInterface $data);

  /**
   * Get address by type of an order.
   *
   * @param int|string $orderId
   *   Order id.
   * @param string $type
   *   Address type defined in OrderAddressServiceInterface constants.
   *
   * @return \Drupal\arch_order\OrderAddressDataInterface|null
   *   The found address data, or NULL if not found.
   */
  public function getByType($orderId, $type = OrderAddressServiceInterface::TYPE_BILLING);

  /**
   * Get addresses of an order.
   *
   * @param int|string $orderId
   *   Order id.
   *
   * @return \Drupal\arch_order\OrderAddressDataInterface[]|null
   *   The found address data, or NULL if not found.
   */
  public function getAddresses($orderId);

}
