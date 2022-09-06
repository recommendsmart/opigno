<?php

namespace Drupal\lbl;

use Drupal\Core\Layout\LayoutDefinition;

/**
 * Interface for css generators.
 */
interface CssGeneratorInterface {

  /**
   * Generate css for layout definition.
   *
   * @param \Drupal\Core\Layout\LayoutDefinition $definition
   *   Layout definition.
   */
  public function build(LayoutDefinition $definition): String|NULL;

  /**
   * Get all breakpoints.
   */
  public function getBreakpoints(): array;

}
