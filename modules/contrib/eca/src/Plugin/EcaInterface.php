<?php

namespace Drupal\eca\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for ECA plugins.
 */
interface EcaInterface extends PluginInspectionInterface {

  /**
   * Provides a list of configuration fields for modellers.
   *
   * @return array
   *   The list of configuration fields.
   */
  public function fields(): array;

}
