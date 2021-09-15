<?php

namespace Drupal\arch_product\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;

/**
 * Defines a controller to render a single product in preview.
 */
class ProductPreviewController extends EntityViewController {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $product_preview, $view_mode_id = 'full', $langcode = NULL) {
    $product_preview->preview_view_mode = $view_mode_id;
    $build = parent::view($product_preview, $view_mode_id);

    $build['#attached']['library'][] = 'arch/drupal.product.preview';

    // Don't render cache previews.
    unset($build['#cache']);

    return $build;
  }

  /**
   * The _title_callback for the page that renders a single product in preview.
   *
   * @param \Drupal\Core\Entity\EntityInterface $product_preview
   *   The current product.
   *
   * @return string
   *   The page title.
   */
  public function title(EntityInterface $product_preview) {
    return $this->entityManager->getTranslationFromContext($product_preview)->label();
  }

}
