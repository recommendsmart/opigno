<?php

namespace Drupal\arch_order\OrderMail\Plugin;

use Drupal\arch_order\OrderMail\OrderMailBase;

/**
 * Order confirmation to shop mail.
 *
 * @package Drupal\arch_order\OrderMail\Plugin
 *
 * @OrderMail(
 *   id = "order_confirmation_to_shop",
 *   label = @Translation("Order confirmation to shop", context = "arch_order_mail"),
 *   description = @Translation("Send to shop when the order is sent.", context = "arch_order_mail"),
 *   sendTo = "shop",
 * )
 */
class OrderConfirmationToShopMail extends OrderMailBase {

}
