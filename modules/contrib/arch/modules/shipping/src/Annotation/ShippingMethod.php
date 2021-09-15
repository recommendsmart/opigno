<?php

namespace Drupal\arch_shipping\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Shipping method annotation object.
 *
 * @package Drupal\arch_shipping\Annotation
 *
 * @Annotation
 */
class ShippingMethod extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the module providing the shipping method plugin.
   *
   * @var string
   */
  public $module;

  /**
   * The form class for configure shipping method.
   *
   * @var string
   */
  public $form;

}
