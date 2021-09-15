<?php

namespace Drupal\arch_order\OrderMail\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Mail annotation object.
 *
 * @package Drupal\arch_order\OrderMail\Annotation
 *
 * @Annotation
 */
class OrderMail extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Plugin label.
   *
   * @var string
   */
  public $label;

  /**
   * The recipient of the email. (user/shop)
   *
   * @var string
   */
  public $sendTo;

  /**
   * Plugin description.
   *
   * @var string
   */
  public $description;

  /**
   * The name of the module providing the mail plugin.
   *
   * @var string
   */
  public $module;

}
