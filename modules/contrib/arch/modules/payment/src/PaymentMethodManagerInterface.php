<?php

namespace Drupal\arch_payment;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Payment method manager interface.
 *
 * @package Drupal\arch_payment
 */
interface PaymentMethodManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface, CacheableDependencyInterface {

  /**
   * Get defined payment methods.
   *
   * @return array|\Drupal\arch_payment\PaymentMethodInterface[]
   *   List of defined payment methods.
   */
  public function getAllPaymentMethods();

  /**
   * Get defined and active payment methods.
   *
   * @return array|\Drupal\arch_payment\PaymentMethodInterface[]
   *   List of defined and active payment methods.
   */
  public function getPaymentMethods();

  /**
   * Get payment method.
   *
   * @param string $id
   *   Payment method plugin ID.
   *
   * @return \Drupal\arch_payment\PaymentMethodInterface|null
   *   Get list of enabled shipping methods.
   */
  public function getPaymentMethod($id);

  /**
   * Get list of available payment methods for given order.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order.
   *
   * @return \Drupal\arch_payment\PaymentMethodInterface[]
   *   List of payment methods available for order.
   */
  public function getAvailablePaymentMethods(OrderInterface $order = NULL);

}
