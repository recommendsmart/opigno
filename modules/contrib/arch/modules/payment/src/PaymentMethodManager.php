<?php

namespace Drupal\arch_payment;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Payment method manager.
 *
 * @package Drupal\arch_payment
 */
class PaymentMethodManager extends DefaultPluginManager implements PaymentMethodManagerInterface {

  /**
   * List of payment methods.
   *
   * @var \Drupal\arch_payment\PaymentMethodInterface[]
   */
  protected $paymentMethods;

  /**
   * Constructs a PaymentMethodManager object.
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
      'Plugin/PaymentMethod',
      $namespaces,
      $module_handler,
      'Drupal\arch_payment\PaymentMethodInterface',
      'Drupal\arch_payment\Annotation\PaymentMethod'
    );

    $this->alterInfo('payment_method');
  }

  /**
   * {@inheritdoc}
   */
  public function getAllPaymentMethods() {
    if (!isset($this->paymentMethods)) {
      $list = [];
      foreach ($this->getDefinitions() as $definition) {
        /** @var \Drupal\arch_payment\PaymentMethodInterface $method */
        $method = $this->createInstance($definition['id']);
        $list[$method->getPluginId()] = $method;
      }

      uasort($list, function (PaymentMethodInterface $a, PaymentMethodInterface $b) {
        if ($a->getWeight() == $b->getWeight()) {
          return 0;
        }
        return ($a->getWeight() < $b->getWeight()) ? -1 : 1;
      });

      $this->paymentMethods = $list;
    }
    return $this->paymentMethods;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethods() {
    return array_filter($this->getAllPaymentMethods(), function ($method) {
      /** @var \Drupal\arch_payment\PaymentMethodInterface $method */
      return $method->isActive();
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethod($id) {
    $this->getPaymentMethods();
    return isset($this->paymentMethods[$id]) ? $this->paymentMethods[$id] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailablePaymentMethods(OrderInterface $order = NULL) {
    if (!$order) {
      return $this->getPaymentMethods();
    }
    return array_filter($this->getPaymentMethods(), function ($method) use ($order) {
      /** @var \Drupal\arch_payment\PaymentMethodInterface $method */
      return $method->isAvailable($order);
    });
  }

}
