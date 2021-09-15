<?php

namespace Drupal\arch_shipping_instore\Plugin\ShippingMethod;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\arch_shipping\ConfigurableShippingMethodBase;

/**
 * In store shipping method.
 *
 * @package Drupal\arch_shipping_instore\Plugin\ShippingMethod
 *
 * @ShippingMethod(
 *   id = "instore",
 *   label = @Translation("In store", context = "arch_shipping_instore"),
 *   forms = {
 *     "configure" = "\Drupal\arch_shipping_instore\Form\AddressOverviewForm",
 *   },
 * )
 */
class InStoreShippingMethod extends ConfigurableShippingMethodBase {

  /**
   * {@inheritdoc}
   */
  public function getShippingPrice(OrderInterface $order) {
    $price = [
      'currency' => $order->get('currency')->getString(),
      'base' => 'gross',
      'net' => 0,
      'gross' => 0,
      'vat_category' => 'custom',
      'vat_rate' => 0,
      'vat_value' => 0,
    ];

    return $this->priceFactory->getInstance($price);
  }

}
