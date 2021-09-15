<?php

namespace Drupal\arch_checkout\CheckoutType;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface;
use Drupal\Core\Plugin\FilteredPluginManagerInterface;

/**
 * Checkout type manager interface.
 *
 * Provides an interface for the discovery and instantiation of checkout type
 * plugins.
 *
 * @package Drupal\arch_checkout\CheckoutType
 */
interface CheckoutTypeManagerInterface extends ContextAwarePluginManagerInterface, CategorizingPluginManagerInterface, FilteredPluginManagerInterface {

  /**
   * Gets the default checkout type.
   *
   * @param bool $throwable
   *   Script could throw exception on getting the default type or not.
   *
   * @return array
   *   Plugin definition as array.
   *
   * @throws \Drupal\arch_checkout\CheckoutType\Exception\CheckoutTypeException
   */
  public function getDefaultCheckoutType($throwable = TRUE);

  /**
   * Check if anonymous checkout is allowed.
   *
   * @return bool
   *   Return TRUE if anonymous users allowed to checkout.
   */
  public function isAnonymousCheckoutAllowed();

  /**
   * Check if we should redirect user to Cart page if it has no item.
   *
   * @return bool
   *   Return TRUE if should redirect.
   */
  public function shouldRedirectIfCartEmpty();

}
