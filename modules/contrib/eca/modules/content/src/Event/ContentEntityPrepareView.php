<?php

namespace Drupal\eca_content\Event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\eca_content\Service\EntityTypes;

/**
 * Provides an event when a content entity is being prepared for viewing.
 *
 * @package Drupal\eca_content\Event
 */
class ContentEntityPrepareView extends ContentEntityBaseEntity {

  /**
   * The displays.
   *
   * @var array
   */
  protected array $displays;

  /**
   * The view mode.
   *
   * @var string
   */
  protected string $viewMode;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\eca_content\Service\EntityTypes $entity_types
   *   The entity types.
   * @param array $displays
   *   The displays.
   * @param string $view_mode
   *   The view mode.
   */
  public function __construct(ContentEntityInterface $entity, EntityTypes $entity_types, array $displays, string $view_mode) {
    parent::__construct($entity, $entity_types);
    $this->displays = $displays;
    $this->viewMode = $view_mode;
  }

  /**
   * Gets the displays.
   *
   * @return array
   *   The displays.
   */
  public function getDisplays(): array {
    return $this->displays;
  }

  /**
   * Gets the view mode.
   *
   * @return string
   *   The view mode.
   */
  public function getViewMode(): string {
    return $this->viewMode;
  }

}
