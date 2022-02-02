<?php

namespace Drupal\digital_signage_computed_content;

/**
 * Class Render.
 *
 * @package Drupal\digital_signage_computed_content
 */
class RenderView implements RenderInterface {

  /**
   * {@inheritdoc}
   */
  public function getMarkup(ComputedContentInterface $entity): array {
    $id = $entity->get('field_view')->getValue()[0]['target_id'];
    $display = $entity->get('field_display')->value;
    return views_embed_view($id, $display);
  }

}
