<?php

namespace Drupal\arch_order\OrderMail\Plugin;

use Drupal\arch_order\OrderMail\OrderMailBase;

/**
 * Order modification mail.
 *
 * @package Drupal\arch_order\OrderMail\Plugin
 *
 * @OrderMail(
 *   id = "order_modification",
 *   label = @Translation("Order modification", context = "arch_order_mail"),
 *   description = @Translation("Send to user when the order is modified.", context = "arch_order_mail"),
 *   sendTo = "user",
 * )
 */
class OrderModificationMail extends OrderMailBase {

}
