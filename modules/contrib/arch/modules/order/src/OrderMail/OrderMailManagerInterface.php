<?php

namespace Drupal\arch_order\OrderMail;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Shipping method manager interface.
 *
 * @package Drupal\arch_order\OrderMail
 */
interface OrderMailManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface, CacheableDependencyInterface {

  /**
   * Get specific mail plugin.
   *
   * @param int $plugin_id
   *   Plugin ID.
   *
   * @return \Drupal\arch_order\OrderMail\OrderMailInterface|null
   *   Mail plugin.
   */
  public function get($plugin_id);

  /**
   * Get all defined mails.
   *
   * @return array|\Drupal\arch_order\OrderMail\OrderMailInterface[]
   *   List of defined mail plugins.
   */
  public function getAll();

  /**
   * Send mail.
   *
   * @param int $plugin_id
   *   Plugin ID.
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order object.
   *
   * @return bool
   *   Result.
   */
  public function send($plugin_id, OrderInterface $order);

}
