<?php

namespace Drupal\arch_shipping;

use Drupal\arch\ArchPluginInterface;
use Drupal\arch_order\Entity\OrderInterface;

/**
 * Shipping method interface.
 *
 * @package Drupal\arch_shipping
 */
interface ShippingMethodInterface extends ArchPluginInterface {

  /**
   * Payment method administrative label.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   Administrative label.
   */
  public function getAdminLabel();

  /**
   * Shipping method label.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   Label.
   */
  public function getLabel();

  /**
   * Shipping method description.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   Description.
   */
  public function getDescription();

  /**
   * Logo image for shipping method.
   *
   * @return \Drupal\Core\Image\Image|null
   *   Logo image.
   */
  public function getImage();

  /**
   * Check if shipping method has description.
   *
   * @return bool
   *   Return TRUE if shipping method has non-empty description.
   */
  public function hasDescription();

  /**
   * Check if shipping method has logo image.
   *
   * @return bool
   *   Return TRUE if shipping method has existing logo image.
   */
  public function hasImage();

  /**
   * Check if this shipping method is available for given address.
   *
   * @param mixed $address
   *   Address.
   *
   * @return bool
   *   Return TRUE if shipping method is available for given address.
   */
  public function isAvailableForAddress($address);

  /**
   * Get shipping price for given order.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order.
   *
   * @return \Drupal\arch_price\Price\PriceInterface|array
   *   Calculated price.
   */
  public function getShippingPrice(OrderInterface $order);

}
