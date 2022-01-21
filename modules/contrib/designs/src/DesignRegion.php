<?php

namespace Drupal\designs;

/**
 * Provides handling of a design region.
 */
class DesignRegion {

  /**
   * Get the definition of the region.
   *
   * @var array
   */
  protected array $definition;

  /**
   * The sources for the region.
   *
   * @var string[]
   */
  protected array $sources;

  /**
   * DesignRegion constructor.
   *
   * @param array $definition
   *   The definition.
   * @param array $sources
   *   The sources for the region.
   */
  public function __construct(array $definition, array $sources) {
    $this->definition = $definition;
    $this->sources = $sources;
  }

  /**
   * Get the definition of the region.
   *
   * @return array
   *   The definition.
   */
  public function getDefinition() {
    return $this->definition;
  }

  /**
   * Get the sources used by the region.
   *
   * @return string[]
   *   The sources.
   */
  public function getSources() {
    return $this->sources;
  }

  /**
   * Build the region content.
   *
   * @param array $element
   *   The source render element.
   * @param array $custom
   *   The custom render sources.
   *
   * @return array
   *   The region render array.
   */
  public function build(array $element, array $custom) {
    $content = [];
    foreach ($this->sources as $source) {
      if (isset($element[$source])) {
        $content[] = $element[$source];
      }
      elseif (isset($custom[$source])) {
        $content[] = $custom[$source];
      }
    }
    return $content;
  }

}
