<?php

namespace Drupal\arch_payment\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Payment method annotation object.
 *
 * @package Drupal\arch_payment\Annotation
 *
 * @Annotation
 */
class PaymentMethod extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the module providing the payment method plugin.
   *
   * @var string
   */
  public $module;

  // @codingStandardsIgnoreStart
  /**
   * Route name which will be called on checkout form submission.
   *
   * @var string
   */
  public $callback_route;
  // @codingStandardsIgnoreEnd

}
