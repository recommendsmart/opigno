<?php

namespace Drupal\eca_content\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca_content\EntityTypeTrait;

/**
 * Class ContentEntityBase
 *
 * @package Drupal\eca_content\Event
 */
abstract class ContentEntityBase extends Event implements ConditionalApplianceInterface {

  use EntityTypeTrait;

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
