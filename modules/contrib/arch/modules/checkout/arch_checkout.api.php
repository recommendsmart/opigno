<?php
/**
 * @file
 * Hooks specific to the Checkout module.
 */

use Drupal\arch_checkout\CheckoutType\CheckoutTypePluginInterface;
use Drupal\arch_order\Entity\OrderInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Checkout type plugin definition alter.
 *
 * @param array $definitions
 *   Checkout plugin list.
 */
function hook_checkout_type_alter(array &$definitions) {
  // @todo Add example implementation.
}

/**
 * Checkout page alter.
 *
 * @param array $form
 *   Checkout page content.
 * @param \Drupal\arch_checkout\CheckoutType\CheckoutTypePluginInterface $checkout_plugin
 *   Checkout plugin.
 */
function hook_checkout_page_alter(array &$form, CheckoutTypePluginInterface $checkout_plugin) {
  // @todo Add example implementation.
}

/**
 * Alter checkout complete page title.
 *
 * @param string $title
 *   Page title.
 * @param \Drupal\arch_order\Entity\OrderInterface $order
 *   Current order.
 */
function hook_checkout_complete_page_title_alter(&$title, OrderInterface $order) {
  // @todo Add example implementation.
}

/**
 * Alter checkout complet page.
 *
 * @param array $output
 *   Render array.
 * @param \Drupal\arch_order\Entity\OrderInterface $order
 *   Current order.
 */
function hook_checkout_complete_page_alter(array &$output, OrderInterface $order) {
  // @todo Add example implementation.
}

/**
 * Respond on order status changed to "completed".
 *
 * @param \Drupal\arch_order\Entity\OrderInterface $order
 *   Changed order.
 */
function hook_checkout_completed(OrderInterface $order) {
  // @todo Add example implementation.
}

/**
 * Alter checkout completed page.
 *
 * @param array $output
 *   Render array.
 * @param \Drupal\arch_order\Entity\OrderInterface $order
 *   Current order.
 */
function hook_checkout_completed_page_alter(array &$output, OrderInterface $order) {
  // @todo add implementation.
}

/**
 * Set flag to allow/disable auto change order status.
 *
 * @param \Drupal\arch_order\Entity\OrderInterface $order
 *   Completed order.
 */
function hook_checkout_complete_page_should_update_order_status(OrderInterface $order) {
  // @todo Add example implementation.
}

/**
 * @} End of "addtogroup hooks".
 */
