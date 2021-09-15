<?php
/**
 * @file
 * Hooks specific to the Arch payment module.
 */

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\arch_payment\PaymentMethodInterface;
use Drupal\arch_price\Price\PriceInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Payment method access.
 *
 * @param \Drupal\arch_payment\PaymentMethodInterface $payment_method
 *   Payment method.
 * @param \Drupal\arch_order\Entity\OrderInterface $order
 *   Order entity.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   User.
 *
 * @return \Drupal\Core\Access\AccessResultReasonInterface
 *   Result.
 */
function hook_payment_method_access(PaymentMethodInterface $payment_method, OrderInterface $order, AccountInterface $account) {
  if (
    $payment_method->getPluginId() === 'saferpay'
    && $order->getOwner()->isAnonymous()
  ) {
    return AccessResult::forbidden();
  }

  return AccessResult::neutral();
}

/**
 * Alter the payment method's fee.
 *
 * @param \Drupal\arch_order\Entity\OrderInterface $order
 *   Order.
 * @param \Drupal\arch_price\Price\PriceInterface $price
 *   Price.
 * @param array $context
 *   An optional context array containing data related to the payment method.
 */
function hook_payment_method_fee_alter(OrderInterface $order, PriceInterface $price, array $context) {
  // @todo Add example.
}

/**
 * @} End of "addtogroup hooks".
 */
