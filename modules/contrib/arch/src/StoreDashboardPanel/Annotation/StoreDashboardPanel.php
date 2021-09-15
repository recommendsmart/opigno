<?php

namespace Drupal\arch\StoreDashboardPanel\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a StoreDashboardPanel annotation object.
 *
 * @Annotation
 */
class StoreDashboardPanel extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  // @codingStandardsIgnoreStart Drupal.NamingConventions.ValidVariableName.LowerCamelName
  /**
   * The administrative label of the panel.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $admin_label = '';
  // @codingStandardsIgnoreEnd Drupal.NamingConventions.ValidVariableName.LowerCamelName

}
