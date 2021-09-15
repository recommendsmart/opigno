<?php

namespace Drupal\arch_checkout\CheckoutType\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a CheckoutType annotation object.
 *
 * @Annotation
 */
class CheckoutType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The administrative label of the panel.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  // @codingStandardsIgnoreStart Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $admin_label = '';
  // @codingStandardsIgnoreEnd Drupal.NamingConventions.ValidVariableName.LowerCamelName

  /**
   * Class name path of the form that accomplish checkout page.
   *
   * @var string
   */
  // @codingStandardsIgnoreStart Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $form_class;
  // @codingStandardsIgnoreEnd Drupal.NamingConventions.ValidVariableName.LowerCamelName

}
