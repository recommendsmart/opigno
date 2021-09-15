<?php

namespace Drupal\arch_product\ContextProvider;

use Drupal\arch_product\Entity\Product;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current product as a context on product routes.
 */
class ProductRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new ProductRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context_definition = EntityContextDefinition::create('product')->setRequired(FALSE);
    $value = NULL;
    if (
      ($route_object = $this->routeMatch->getRouteObject())
      && ($route_contexts = $route_object->getOption('parameters'))
      && isset($route_contexts['product'])
    ) {
      if ($product = $this->routeMatch->getParameter('product')) {
        $value = $product;
      }
    }
    elseif ($this->routeMatch->getRouteName() == 'product.add') {
      $product_type = $this->routeMatch->getParameter('product_type');
      $value = Product::create(['type' => $product_type->id()]);
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);
    $result['product'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = EntityContext::fromEntityTypeId(
      'product',
      $this->t('Product from URL', [], ['context' => 'arch_product'])
    );
    return ['product' => $context];
  }

}
