<?php
/**
 * @file
 * Hooks specific to the Price module.
 */

use Drupal\arch_price\Plugin\Field\FieldType\PriceItemInterface;
use Drupal\arch_price\Price\PriceInterface;
use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\currency\Entity\CurrencyInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Controls access to a price.
 *
 * @param \Drupal\arch_price\Plugin\Field\FieldType\PriceItemInterface $item
 *   Price item.
 * @param \Drupal\arch_product\Entity\ProductInterface $product
 *   Product.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   Account.
 *
 * @return \Drupal\Core\Access\AccessResultInterface
 *   Result.
 */
function hook_price_access(PriceItemInterface $item, ProductInterface $product, AccountInterface $account) {
  $available_for_roles = $product->get('field_available_for_role')->getValue();
  $roles = $account->getRoles();
  foreach ($roles as $role) {
    if (in_array($role, $available_for_roles)) {
      return AccessResult::allowed();
    }
  }
  return AccessResult::forbidden();
}

/**
 * Alter list of available prices.
 *
 * @param \Drupal\arch_price\Plugin\Field\FieldType\PriceItemInterface[] $prices
 *   List of all available prices.
 * @param \Drupal\arch_product\Entity\ProductInterface $product
 *   Product.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   Account.
 */
function hook_product_available_prices_alter(array &$prices, ProductInterface $product, AccountInterface $account) {
  // @todo add example implementation.
}

/**
 * Alter list of product prices.
 *
 * @param \Drupal\arch_price\Plugin\Field\FieldType\PriceItemInterface[] $price_list
 *   List of all available prices.
 * @param \Drupal\arch_product\Entity\ProductInterface $product
 *   Product.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   Account.
 */
function hook_price_negotiation_prices_alter(array &$price_list, ProductInterface $product, AccountInterface $account) {
  // @todo add example implementation.
}

/**
 * Alter list of product prices when product has no available price.
 *
 * @param \Drupal\arch_price\Price\PriceInterface[] $prices
 *   Price list.
 * @param \Drupal\arch_product\Entity\ProductInterface $product
 *   Product.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   Account.
 */
function hook_price_negotiation_empty_price_list_alter(array &$prices, ProductInterface $product, AccountInterface $account) {
  // @todo add example implementation.
}

/**
 * Alter list of available prices.
 *
 * @param \Drupal\arch_price\Price\PriceInterface $price
 *   Price.
 * @param array $context
 *   Alter context with keys:
 *   - product \Drupal\arch_product\Entity\ProductInterface
 *   - account \Drupal\Core\Session\AccountInterface
 *   - prices \Drupal\arch_price\Plugin\Field\FieldType\PriceItemInterface[].
 */
function hook_product_active_price_alter(PriceInterface &$price, array &$context) {
  // @todo add example implementation.
}

/**
 * Alter list of available price types.
 *
 * @param \Drupal\arch_price\Entity\PriceTypeInterface[] $price_types
 *   Price type list.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   Account.
 * @param string $operation
 *   Operation.
 */
function hook_arch_available_price_types_alter(array &$price_types, AccountInterface $account, $operation) {
  // @todo add example implementation.
}

/**
 * Alter price display settings.
 *
 * @param array $settings
 *   Display settings.
 * @param array $values
 *   Price values.
 * @param \Drupal\currency\Entity\CurrencyInterface $currency
 *   Selected currency.
 */
function hook_arch_price_display_settings(array &$settings, array $values, CurrencyInterface $currency) {
  // @todo add example implementation.
}

/**
 * @} End of "addtogroup hooks".
 */
