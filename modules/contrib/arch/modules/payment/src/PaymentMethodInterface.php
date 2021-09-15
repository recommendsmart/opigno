<?php

namespace Drupal\arch_payment;

use Drupal\arch\ArchPluginInterface;
use Drupal\arch_order\Entity\OrderInterface;

/**
 * Payment method interface.
 *
 * @package Drupal\arch_payment
 */
interface PaymentMethodInterface extends ArchPluginInterface {

  /**
   * Payment method administrative label.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   Administrative label.
   */
  public function getAdminLabel();

  /**
   * Payment method label.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   Label.
   */
  public function getLabel();

  /**
   * Payment method description.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   Description.
   */
  public function getDescription();

  /**
   * Logo image for payment method.
   *
   * @return \Drupal\Core\Image\Image|null
   *   Logo image.
   */
  public function getImage();

  /**
   * Check if payment method has description.
   *
   * @return bool
   *   Return TRUE if payment method has non-empty description.
   */
  public function hasDescription();

  /**
   * Check if payment method has logo image.
   *
   * @return bool
   *   Return TRUE if payment method has existing logo image.
   */
  public function hasImage();

  /**
   * Payment method callback route.
   *
   * @return string
   *   Route name which will be called on checkout form submission.
   */
  public function getCallbackRoute();

  /**
   * Returns the price of the payment fee.
   *
   * @return \Drupal\arch_price\Price\PriceInterface|null
   *   Payment fee price.
   */
  public function getPaymentFee(OrderInterface $order);

}
