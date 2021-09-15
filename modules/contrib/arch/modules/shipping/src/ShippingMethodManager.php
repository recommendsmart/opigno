<?php

namespace Drupal\arch_shipping;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Shipping method manager.
 *
 * @package Drupal\arch_shipping
 */
class ShippingMethodManager extends DefaultPluginManager implements ShippingMethodManagerInterface {

  /**
   * List of shipping methods.
   *
   * @var \Drupal\arch_shipping\ShippingMethodInterface[]
   */
  protected $shippingMethods;

  /**
   * Constructs a ShippingMethodManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/ShippingMethod',
      $namespaces,
      $module_handler,
      'Drupal\arch_shipping\ShippingMethodInterface',
      'Drupal\arch_shipping\Annotation\ShippingMethod'
    );

    $this->alterInfo('shipping_methods_plugin');
  }

  /**
   * {@inheritdoc}
   */
  public function getAllShippingMethods() {
    if (!isset($this->shippingMethods)) {
      $list = [];
      foreach ($this->getDefinitions() as $definition) {
        $method = $this->createInstance(
          $definition['id'],
          []
        );
        $list[$method->getPluginId()] = $method;
      }

      $this->shippingMethods = $list;
    }
    return $this->shippingMethods;
  }

  /**
   * {@inheritdoc}
   */
  public function getShippingMethods() {
    return array_filter($this->getAllShippingMethods(), function ($method) {
      /** @var \Drupal\arch_shipping\ShippingMethodInterface $method */
      return $method->isActive();
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getShippingMethod($id) {
    $this->getShippingMethods();
    return isset($this->shippingMethods[$id]) ? $this->shippingMethods[$id] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableShippingMethods(OrderInterface $order = NULL) {
    if (!$order) {
      return $this->getShippingMethods();
    }
    return array_filter($this->getShippingMethods(), function ($method) use ($order) {
      /** @var \Drupal\arch_shipping\ShippingMethodInterface $method */
      return $method->isAvailable($order);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableShippingMethodsForAddress($address) {
    return array_filter($this->getShippingMethods(), function ($method) use ($address) {
      /** @var \Drupal\arch_shipping\ShippingMethodInterface $method */
      return $method->isAvailableForAddress($address);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getShippingPrices(OrderInterface $order) {
    $prices = [];
    foreach ($this->getAvailableShippingMethods($order) as $method) {
      $prices[$method->getPluginId()] = $method->getShippingPrice($order);
    }
    return $prices;
  }

}
