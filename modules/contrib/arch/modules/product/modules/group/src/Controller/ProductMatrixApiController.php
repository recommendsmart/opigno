<?php

namespace Drupal\arch_product_group\Controller;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\arch_product_group\Ajax\ProductReplaceContentCommand;
use Drupal\arch_product_group\GroupHandlerInterface;
use Drupal\arch_product_group\ProductMatrixInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Product matrix API controller.
 *
 * @package Drupal\arch_product_group\Controller
 */
class ProductMatrixApiController extends ControllerBase {

  /**
   * Group handler.
   *
   * @var \Drupal\arch_product_group\GroupHandlerInterface
   */
  protected $groupHandler;

  /**
   * Product matrix.
   *
   * @var \Drupal\arch_product_group\ProductMatrixInterface
   */
  protected $productMatrix;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Product view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $productViewBuilder;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Current language.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $currentLanguage;

  /**
   * Route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * ProductMatrixApiController constructor.
   *
   * @param \Drupal\arch_product_group\GroupHandlerInterface $group_handler
   *   Group handler.
   * @param \Drupal\arch_product_group\ProductMatrixInterface $product_matrix
   *   Product matrix.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   Entity display repository.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Route match.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   */
  public function __construct(
    GroupHandlerInterface $group_handler,
    ProductMatrixInterface $product_matrix,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    EntityFieldManagerInterface $entity_field_manager,
    LanguageManagerInterface $language_manager,
    RouteMatchInterface $route_match,
    RendererInterface $renderer
  ) {
    $this->groupHandler = $group_handler;
    $this->productMatrix = $product_matrix;
    $this->entityTypeManager = $entity_type_manager;
    $this->productViewBuilder = $entity_type_manager->getViewBuilder('product');
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->currentLanguage = $language_manager->getCurrentLanguage();
    $this->routeMatch = $route_match;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('product_group.handler'),
      $container->get('product_matrix'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity_field.manager'),
      $container->get('language_manager'),
      $container->get('current_route_match'),
      $container->get('renderer')
    );
  }

  /**
   * Get rendered product.
   *
   * @param int $group_id
   *   Group ID.
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   Requested product.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   *
   * @throws \Exception
   */
  public function product($group_id, ProductInterface $product) {
    if (
      !$this->groupHandler->isPartOfGroup($product)
      || $this->groupHandler->getGroupId($product) !== (int) $group_id
    ) {
      throw new NotFoundHttpException();
    }

    $langcode = $this->currentLanguage->getId();

    if ($product->hasTranslation($langcode)) {
      $product = $product->getTranslation($langcode);
    }

    $build = $this->productViewBuilder->view($product, 'full');
    $build['#page'] = TRUE;
    $content = $this->renderer->render($build);

    $selector = [
      '.product-' . $product->bundle(),
      '.product--full',
      '.product-' . $product->bundle() . '-full',
    ];
    $ajax_url = Url::fromRoute($this->routeMatch->getRouteName(), [
      'group_id' => (int) $group_id,
      'product' => (int) $product->id(),
    ]);
    $ajax_url->setAbsolute(FALSE);

    $response = new AjaxResponse();
    $response->addCommand(new ProductReplaceContentCommand(
      $product->toUrl(),
      $product->id() . '#' . $product->label(),
      implode('', $selector),
      $content,
      [
        'group_id' => (int) $group_id,
        'product_id' => (int) $product->id(),
        'ajax_url' => $ajax_url->toString(),
      ]
    ));

    return $response;
  }

}
