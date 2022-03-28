<?php
/**
 * @file
 * Hooks specific to the Arch order module.
 */

/**
 * @addtogroup hooks
 * @{
 */

use Drupal\arch_cart\Cart\CartInterface;
use Drupal\arch_order\Plugin\Field\FieldType\OrderLineItemInterface;
use Drupal\arch_product\Entity\Product;

/**
 * Alter mail params before build content.
 *
 * @param array $token_params
 *   Token params passed to Token::replace() method.
 * @param array $context
 *   Mail context data.
 */
function hook_arch_order_mail_params_alter(array &$token_params, array &$context) {
  // @todo Add example.
}

/**
 * Alter order data before created from cart.
 *
 * @param array $data
 *   Order data passed to Order::create().
 * @param \Drupal\arch_cart\Cart\CartInterface $cart
 *   Cart instance.
 */
function hook_arch_order_create_from_cart_data_alter(array &$data, CartInterface $cart) {
  foreach ($data['line_items'] as $index => $line_item) {
    if ($line_item['type'] != OrderLineItemInterface::ORDER_LINE_ITEM_TYPE_PRODUCT) {
      continue;
    }
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = Product::load($line_item['product_id']);
    if (
      !$product
      || empty($line_item['quantity'])
    ) {
      unset($data['line_items'][$index]);
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
