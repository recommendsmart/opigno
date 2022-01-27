<?php

namespace Drupal\dimension\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\DecimalFormatter;
use Drupal\dimension\Plugin\Field\Basic;

/**
 * Abstract class for length, area and volume formatters.
 */
abstract class Dimension extends DecimalFormatter implements Basic {

}
