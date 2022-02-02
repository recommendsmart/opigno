<?php

namespace Drupal\dimension\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Drupal\dimension\Plugin\Field\Basic;

/**
 * Abstract class for length, area and volume formatters.
 */
abstract class Components extends StringFormatter implements Basic {

  protected function viewValue(FieldItemInterface $item) {
    $build = [
      '#theme' => $this->pluginId,
    ];
    foreach ($this->fields() as $name => $label) {
      $build['#' . $name] = $item->get($name)->getValue();
    }
    return $build;
  }

}
