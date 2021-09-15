<?php
/**
 * @file
 * Hooks specific to the OnepageCheckout module.
 */

use Drupal\arch_cart\Cart\CartInterface;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Shipping addresses.
 */
function hook_commerce_shipping_addresses() {
  // @todo Add example implementation.
}

/**
 * Alter shipping addresses.
 *
 * @param array $addresses
 *   Shipping addresses.
 */
function hook_commerce_shipping_addresses_alter(array &$addresses) {
  // @todo Add example implementation.
}

/**
 * React on shipping method change.
 *
 * @param \Drupal\arch_shipping\ShippingMethodInterface|null $shipping_method
 *   Selected shipping method.
 * @param \Drupal\Core\Ajax\AjaxResponse $response
 *   AjaxResponse.
 * @param \Drupal\arch_cart\Cart\CartInterface $cart
 *   Cart instance.
 */
function hook_shipping_method_changed($shipping_method, AjaxResponse $response, CartInterface $cart) {
  // @todo Add example implementation.
}

/**
 * @} End of "addtogroup hooks".
 */
