<?php

namespace Drupal\eca_content\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Event\ContentEntityEventInterface;

/**
 * Base class for field selection events.
 */
abstract class FieldSelectionBase extends Event implements ConditionalApplianceInterface, ContentEntityEventInterface {}
