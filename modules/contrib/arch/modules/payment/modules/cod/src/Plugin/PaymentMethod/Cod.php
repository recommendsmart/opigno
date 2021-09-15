<?php

namespace Drupal\arch_payment_cod\Plugin\PaymentMethod;

use Drupal\arch_payment\PaymentMethodBase;

/**
 * Defines the transfer payment for payment methods plugins.
 *
 * @PaymentMethod(
 *   id = "cod",
 *   label = @Translation("Cash on delivery", context = "arch_payment_cod"),
 *   administrative_label = @Translation("Cash on delivery", context = "arch_payment_cod"),
 *   description = @Translation("Payment method to checkout with cash on delivery.", context = "arch_payment_cod"),
 *   module = "arch_payment_cod",
 *   callback_route = "arch_payment_cod.success"
 * )
 */
class Cod extends PaymentMethodBase {

}
