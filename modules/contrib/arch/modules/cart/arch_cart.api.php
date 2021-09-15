<?php
/**
 * @file
 * Hooks specific to the Price module.
 */

use Drupal\arch_cart\Cart\CartInterface;
use Drupal\arch_price\Price\PriceInterface;
use Drupal\arch_order\Entity\OrderInterface;
use Drupal\arch_product\Entity\Product;
use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\file\Entity\File;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Modify rendered image for product when displayed on cart form.
 *
 * @param array $image
 *   Render array.
 * @param array $context
 *   Alter context: contains line item, product and cart instance.
 */
function hook_arch_cart_product_image_alter(array &$image, array &$context) {
  // @todo add example implementation.
}

/**
 * Modify rendered product properties when displayed on cart form.
 *
 * @param array $properties
 *   Render array.
 * @param array $context
 *   Alter context: contains line item, product and cart instance.
 */
function hook_arch_cart_product_properties_alter(array &$properties, array &$context) {
  // @todo add example implementation.
}

/**
 * Modify product details when displayed on cart form.
 *
 * @param array $details
 *   Render array.
 * @param array $context
 *   Alter context: contains line item, product and cart instance.
 */
function hook_arch_cart_product_details_alter(array &$details, array &$context) {
  // @todo add example implementation.
}

/**
 * Modify product table row on cart form.
 *
 * @param array $build
 *   Product row render array.
 * @param array $context
 *   Alter context: contains line item, product and cart instance.
 */
function hook_arch_cart_product_table_row_alter(array &$build, array &$context) {
  /** @var \Drupal\arch_product\Entity\ProductInterface $product */
  $product = $context['product'];
  $build['#attributes']['data-product-sku'] = $product->getSku();
}

/**
 * Define access check for minicart display.
 *
 * @return \Drupal\Core\Access\AccessResultInterface
 *   Access result.
 */
function hook_arch_minicart_display_allowed() {
  $current_user = \Drupal::currentUser();
  $result = AccessResult::forbiddenIf($current_user->isAnonymous());
  return $result;
}

/**
 * Alter access result object for minicart.
 *
 * @param \Drupal\Core\Access\AccessResultInterface $result
 *   Access result.
 */
function hook_arch_minicart_display_allowed_alter(AccessResultInterface &$result) {
  // @todo add example implementation.
}

/**
 * Set product image.
 *
 * @param string $image_uri
 *   Image URI.
 * @param \Drupal\arch_product\Entity\ProductInterface $product
 *   Product entity.
 */
function hook_arch_cart_mini_cart_product_image_alter(&$image_uri, ProductInterface $product) {
  if (!empty($image_uri)) {
    return;
  }

  if ($product->hasField('field_product_image')) {
    $image = current($product->get('field_product_image')->getValue());
  }
  elseif ($product->hasField('field_lead_image')) {
    $image = current($product->get('field_lead_image')->getValue());
  }

  if (
    !empty($image)
    && !empty($image['target_id'])
  ) {
    /** @var \Drupal\file\Entity\File $image_file */
    if ($image_file = File::load($image['target_id'])) {
      /** @var \Drupal\file\FileInterface $file */
      $image_uri = $image_file->getFileUri();
    }
  }
}

/**
 * Alter used image style for product display in minicart.
 *
 * @param string $image_style_id
 *   Image style ID.
 * @param \Drupal\arch_product\Entity\ProductInterface $product
 *   Displayed product.
 */
function hook_arch_cart_mini_cart_product_image_style_alter(&$image_style_id, ProductInterface $product) {
  // @todo add implementation.
}

/**
 * Respond on cart change.
 *
 * @param string $type
 *   Change type.
 * @param array|null $item
 *   Modified item or NULL if item has removed.
 * @param array|null $old_item
 *   Previous item value of NULL if item is a new one.
 * @param array $items
 *   Cart item list.
 */
function hook_arch_cart_change($type, &$item, &$old_item, array &$items) {
  // Prevent overselling items. If user tries to put more item to cart then
  // available stock we correct the values.
  if (in_array($type, [CartInterface::ITEM_NEW, CartInterface::ITEM_UPDATE])) {
    /** @var \Drupal\arch_stock\StockKeeperInterface $stock_keeper */
    $stock_keeper = \Drupal::service('arch_stock.stock_keeper');
    $current_user = \Drupal::currentUser();
    foreach ($items as &$item) {
      if (empty($item['type']) || $item['type'] !== 'product') {
        continue;
      }
      $product = Product::load($item['id']);
      $total = $stock_keeper->getTotalProductStock($product, $current_user);
      if ($item['quantity'] > $total) {
        $item['quantity'] = $total;
      }
    }
  }
}

/**
 * Modify API Cart data.
 *
 * @param array $data
 *   Cart data.
 * @param \Drupal\arch_cart\Cart\CartInterface $cart
 *   Cart.
 */
function hook_api_cart_data_alter(array &$data, CartInterface $cart) {

}

/**
 * Respond when new product has been placed into cart.
 *
 * @param array|null $item
 *   Modified item or NULL if item has removed.
 * @param array|null $old_item
 *   Previous item value of NULL if item is a new one.
 * @param array $items
 *   Cart item list.
 * @param \Drupal\arch_cart\Cart\CartInterface $cart
 *   Cart instance.
 */
function hook_arch_cart_item_new(&$item, &$old_item, array &$items, CartInterface $cart) {
  // @todo add example implementation.
}

/**
 * Respond when product has been changed in cart.
 *
 * @param array|null $item
 *   Modified item or NULL if item has removed.
 * @param array|null $old_item
 *   Previous item value of NULL if item is a new one.
 * @param array $items
 *   Cart item list.
 */
function hook_arch_cart_item_update(&$item, &$old_item, array &$items, CartInterface $cart) {
  // @todo add example implementation.
}

/**
 * Respond when new product has been removed from cart.
 *
 * @param array|null $item
 *   Modified item or NULL if item has removed.
 * @param array|null $old_item
 *   Previous item value of NULL if item is a new one.
 * @param array $items
 *   Cart item list.
 */
function hook_arch_cart_item_remove(&$item, &$old_item, array &$items, CartInterface $cart) {
  // @todo add example implementation.
}

/**
 * Alter calculated shipping price.
 *
 * @param \Drupal\arch_price\Price\PriceInterface $price
 *   Calculated shipping price.
 * @param \Drupal\arch_cart\Cart\CartInterface $cart
 *   Cart instance.
 * @param \Drupal\arch_order\Entity\OrderInterface $order
 *   Order instance.
 */
function hook_shipping_price_alter(PriceInterface $price, CartInterface $cart, OrderInterface $order) {
  // @todo Add example implementation.
}

/**
 * Alter calculated shipping price.
 *
 * @param \Drupal\arch_price\Price\PriceInterface $price
 *   Calculated shipping price.
 * @param \Drupal\arch_cart\Cart\CartInterface $cart
 *   Cart instance.
 * @param \Drupal\arch_order\Entity\OrderInterface $order
 *   Order instance.
 */
function hook_shipping_price_SHIPPING_METHOD_ID_alter(PriceInterface $price, CartInterface $cart, OrderInterface $order) {
  // @todo Add example implementation.
}

/**
 * Alter total base values.
 *
 * @param array $total_base_values
 *   Total base values that describes a price object.
 */
function hook_cart_total_base_values_alter(array $total_base_values) {
  // @todo Add example implementation.
}

/**
 * @} End of "addtogroup hooks".
 */
