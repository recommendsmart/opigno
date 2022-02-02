<?php

namespace Drupal\digital_signage_computed_content;

/**
 * Interface RenderInterface.
 *
 * @package Drupal\digital_signage_computed_content
 */
interface RenderInterface {

  /**
   * Render the entity.
   *
   * @param \Drupal\digital_signage_computed_content\ComputedContentInterface $entity
   *   The entity which should be rendered.
   *
   * @return array
   *   Renderable array for this entity.
   */
  public function getMarkup(ComputedContentInterface $entity): array;

}
