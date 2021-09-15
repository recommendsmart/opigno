<?php

namespace Drupal\arch_checkout\Services;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checkout complete page access.
 *
 * @package Drupal\arch_checkout\Services
 */
class CheckoutCompletePageAccess implements ContainerInjectionInterface {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * CheckoutCompletePageAccess constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user.
   */
  public function __construct(
    ModuleHandlerInterface $moduleHandler,
    AccountProxyInterface $currentUser
  ) {
    $this->moduleHandler = $moduleHandler;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * Checks whether checkout complete page is accessible or not.
   *
   * @return bool
   *   Accessible or not.
   */
  public function canAccess(OrderInterface $order) {
    if ($order->getProductsCount() < 1) {
      return FALSE;
    }

    $access = AccessResult::neutral();
    if ($order->get('status')->getString() != 'cart') {
      $access = AccessResult::forbidden('Order is not in CART status.');
    }

    $access_fallback = clone $access;

    $this->moduleHandler->alter('order_access_checkout_complete', $order, $access);

    if (!($access instanceof AccessResultInterface)) {
      $access = $access_fallback;
    }

    return !$access->isForbidden();
  }

}
