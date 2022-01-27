<?php

namespace Drupal\digital_signage_framework\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines digital_signage_schedule_generator annotation object.
 *
 * @Annotation
 */
class DigitalSignageScheduleGenerator extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
