<?php

namespace Drupal\eca_content\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Event\ConditionalApplianceInterface;

/**
 * Base class for entity related events.
 */
abstract class ContentEntityBase extends Event implements ConditionalApplianceInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    return TRUE;
  }

}
