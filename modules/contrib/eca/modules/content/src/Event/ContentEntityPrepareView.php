<?php

namespace Drupal\eca_content\Event;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class ContentEntityPrepareView
 *
 * @package Drupal\eca_content\Event
 */
class ContentEntityPrepareView extends ContentEntityBaseEntity {

  /**
   * @var array
   */
  protected array $displays;

  /**
   * @var string
   */
  protected string $viewMode;

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param array $displays
   * @param string $view_mode
   */
  public function __construct(ContentEntityInterface $entity, array $displays, string $view_mode) {
    parent::__construct($entity);
    $this->displays = $displays;
    $this->viewMode = $view_mode;
  }

  /**
   * @return array
   */
  public function getDisplays(): array {
    return $this->displays;
  }

  /**
   * @return string
   */
  public function getViewMode(): string {
    return $this->viewMode;
  }

}
