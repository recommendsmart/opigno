<?php

namespace Drupal\eca\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for ECA plugins.
 */
interface EcaInterface extends PluginInspectionInterface {

  /**
   * @return array
   */
  public function fields(): array;

}
