<?php

namespace Drupal\form_display_visibility\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a form display visibility annotation object.
 *
 * @Annotation
 */
class FormDisplayVisibilityCondition extends Plugin {

  /**
   * The plugin ID of the event operation.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the event operation.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the event operation.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
