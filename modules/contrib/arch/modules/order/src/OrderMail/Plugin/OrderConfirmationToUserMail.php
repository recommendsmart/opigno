<?php

namespace Drupal\arch_order\OrderMail\Plugin;

use Drupal\arch_order\OrderMail\OrderMailBase;

/**
 * Order confirmation mail.
 *
 * @package Drupal\arch_order\OrderMail\Plugin
 *
 * @OrderMail(
 *   id = "order_confirmation_to_user",
 *   label = @Translation("Order confirmation to user", context = "arch_order_mail"),
 *   description = @Translation("Send to user when the order is sent.", context = "arch_order_mail"),
 *   sendTo = "user",
 * )
 */
class OrderConfirmationToUserMail extends OrderMailBase {

}
