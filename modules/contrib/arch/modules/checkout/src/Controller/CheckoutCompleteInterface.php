<?php

namespace Drupal\arch_checkout\Controller;

use Drupal\arch_order\Entity\OrderInterface;

/**
 * Checkout complete interface.
 *
 * @package Drupal\arch_checkout\Controller
 */
interface CheckoutCompleteInterface {

  /**
   * Text which appears in the first line of checkout complete page.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Translatable string.
   */
  public function checkoutCompleteStatusMessage();

  /**
   * Checkout complete info render array.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order entity.
   *
   * @return array
   *   Renderable array of checkout complete info.
   */
  public function checkoutCompleteInfo(OrderInterface $order);

}
