<?php

namespace Drupal\arch_payment_saferpay\Saferpay;

/**
 * Saferpay handler interface.
 *
 * @package Drupal\arch_payment_saferpay\Saferpay
 */
interface SaferpayHandlerInterface {

  /**
   * Set order by ID.
   *
   * @param int $order_id
   *   Order ID.
   *
   * @return \Drupal\arch_order\Entity\OrderInterface|null
   *   Returns found OrderInterface instance.
   */
  public function setOrder($order_id);

  /**
   * Calls connection initialization endpoint.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   Response data.
   */
  public function callInitialize();

  /**
   * Calls assertion endpoint.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   Response data.
   */
  public function callAssert();

}
