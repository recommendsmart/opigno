<?php

namespace Drupal\arch_order\OrderMail\Plugin;

use Drupal\arch_order\OrderMail\OrderMailBase;

/**
 * Order status change mail.
 *
 * @package Drupal\arch_order\OrderMail\Plugin
 *
 * @OrderMail(
 *   id = "order_status_change",
 *   label = @Translation("Order status change", context = "arch_order_mail"),
 *   description = @Translation("Send to user when the status of the order is changed.", context = "arch_order_mail"),
 *   sendTo = "user",
 * )
 */
class OrderStatusChangeMail extends OrderMailBase {

}
