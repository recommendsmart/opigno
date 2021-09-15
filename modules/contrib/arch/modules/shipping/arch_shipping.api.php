<?php
/**
 * @file
 * Hooks specific to the Arch shipping module.
 */

use Drupal\arch_shipping\ShippingMethodInterface;
use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter dashboard page.
 *
 * @param \Drupal\arch_shipping\ShippingMethodInterface $shipping_method
 *   Shipping method.
 * @param \Drupal\arch_order\Entity\OrderInterface $order
 *   Order entity.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   User.
 *
 * @return \Drupal\Core\Access\AccessResultReasonInterface
 *   Result.
 */
function hook_shipping_method_access(ShippingMethodInterface $shipping_method, OrderInterface $order, AccountInterface $account) {
  if (
    $shipping_method->getPluginId() === 'instore'
    && $order->getOwner()->isAnonymous()
  ) {
    return AccessResult::forbidden();
  }

  return AccessResult::neutral();
}

/**
 * @} End of "addtogroup hooks".
 */
