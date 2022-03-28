<?php

namespace Drupal\arch_product\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to render a single product in preview.
 */
class ProductPreviewController extends EntityViewController {

  /**
   * Entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer,
    EntityRepositoryInterface $entity_repository
  ) {
    parent::__construct(
      $entity_type_manager,
      $renderer
    );
    $this->entityRepository = $entity_repository;
  }

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
    return $this->entityRepository->getTranslationFromContext($product_preview)->label();
  }

}
