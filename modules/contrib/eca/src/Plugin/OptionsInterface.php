<?php

namespace Drupal\eca\Plugin;

/**
 * Interface for actions and conditions with select-type config field(s).
 */
interface OptionsInterface {

  /**
   * Returns a key array of values with all available options.
   *
   * @param string $id
   *   The id of the configuration value for which to receive the options.
   *
   * @return array|null
   *   The keyed array with option values. NULL if the field $id has no options.
   */
  public function getOptions(string $id): ?array;

}
