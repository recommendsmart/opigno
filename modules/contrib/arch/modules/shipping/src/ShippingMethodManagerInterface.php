<?php

namespace Drupal\arch_shipping;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Shipping method manager interface.
 *
 * @package Drupal\arch_shipping
 */
interface ShippingMethodManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface, CacheableDependencyInterface {

  /**
   * Get defined shipping methods.
   *
   * @return array|\Drupal\arch_shipping\ShippingMethodInterface[]
   *   List of defined shipping method plugins.
   */
  public function getAllShippingMethods();

  /**
   * Get defined and active shipping methods.
   *
   * @return \Drupal\arch_shipping\ShippingMethodInterface[]
   *   List of defined and active shipping method plugins.
   */
  public function getShippingMethods();

  /**
   * Get shipping method.
   *
   * @param string $id
   *   Shipping method plugin ID.
   *
   * @return \Drupal\arch_shipping\ShippingMethodInterface|null
   *   Get list of enabled shipping methods.
   */
  public function getShippingMethod($id);

  /**
   * Get list of available shipping methods for given order.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order.
   *
   * @return \Drupal\arch_shipping\ShippingMethodInterface[]
   *   List of shipping methods available for order.
   */
  public function getAvailableShippingMethods(OrderInterface $order = NULL);

  /**
   * Get filtered list of shipping methods available for given address.
   *
   * @param mixed $address
   *   Address.
   *
   * @return \Drupal\arch_shipping\ShippingMethodInterface[]
   *   Filtered list of shipping methods.
   */
  public function getAvailableShippingMethodsForAddress($address);

  /**
   * Get list of calculated shipping prices by available shipping methods.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order.
   *
   * @return array
   *   List of calculted prices where key is the ID of the shipping method,
   *   value is the calculated price.
   */
  public function getShippingPrices(OrderInterface $order);

}
