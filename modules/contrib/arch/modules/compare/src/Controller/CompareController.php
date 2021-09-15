<?php

namespace Drupal\arch_compare\Controller;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\arch_product\Entity\ProductTypeInterface;
use Drupal\arch_product\Entity\Storage\ProductStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Compare product controller.
 */
class CompareController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Compare settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Product storage.
   *
   * @var \Drupal\arch_product\Entity\Storage\ProductStorageInterface
   */
  protected $productStorage;

  /**
   * Product type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $productTypeStorage;

  /**
   * CompareController constructor.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Compare settings.
   * @param \Symfony\Component\HttpFoundation\Request $current_request
   *   Current request.
   * @param \Drupal\arch_product\Entity\Storage\ProductStorageInterface $product_storage
   *   Product storage.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $product_type_storage
   *   ProductType storage.
   */
  public function __construct(
    ImmutableConfig $config,
    Request $current_request,
    ProductStorageInterface $product_storage,
    ConfigEntityStorageInterface $product_type_storage
  ) {
    $this->settings = $config;
    $this->request = $current_request;
    $this->productStorage = $product_storage;
    $this->productTypeStorage = $product_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')->get('arch_compare.settings'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('entity_type.manager')->getStorage('product'),
      $container->get('entity_type.manager')->getStorage('product_type')
    );
  }

  /**
   * Page callback.
   */
  public function page() {
    $limit = (int) $this->settings->get('limit');
    $product_ids = $this->request->query->get('products');
    if (empty($product_ids)) {
      throw new NotFoundHttpException();
    }

    if (count($product_ids) > $limit) {
      $product_ids = array_splice($product_ids, 0, $limit);
      return $this->redirect('arch_compare.compare_page', ['products' => $product_ids]);
    }

    $comparable = array_filter($this->productTypeStorage->loadMultiple(), function (ProductTypeInterface $product_type) {
      return $product_type->getThirdPartySetting('arch_compare', 'comparable');
    });
    if (empty($comparable)) {
      throw new NotFoundHttpException();
    }

    /** @var \Drupal\arch_product\Entity\ProductInterface[] $products */
    $products = array_filter($this->productStorage->loadMultiple($product_ids), function (ProductInterface $product) use ($comparable) {
      return isset($comparable[$product->bundle()]);
    });

    if (
      empty($products)
      || count($products) < 2
    ) {
      throw new NotFoundHttpException();
    }

    $cache_tags = [];
    foreach ($products as $product) {
      $cache_tags = array_merge($cache_tags, $product->getCacheTags());
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'compare--page-body',
          'compare--max-' . $limit,
          'compare--count-' . count($products),
        ],
      ],
      'content' => [
        '#theme' => 'compare_page',
        '#products' => $products,
        '#limit' => $limit,
        '#view_mode' => $this->settings->get('view_mode'),
      ],
      '#attached' => [
        'library' => [
          'arch_compare/compare_products',
        ],
      ],
      '#cache' => [
        'contexts' => ['url'],
        'tags' => $cache_tags,
      ],
    ];
  }

}
