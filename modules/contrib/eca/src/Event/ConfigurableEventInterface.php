<?php

namespace Drupal\eca\Event;

/**
 * Interface for events that support configurable fields.
 */
interface ConfigurableEventInterface {

  /**
   * Provides field specifications for the modeller.
   *
   * @return string[]
   *   The field specification.
   */
  public static function fields(): array;

}
