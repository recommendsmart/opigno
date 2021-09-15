<?php

namespace Drupal\arch_product\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for products.
 */
class ProductRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();
    $route = (new Route('/product/{product}'))
      ->addDefaults([
        '_controller' => '\Drupal\arch_product\Controller\ProductViewController::view',
        '_title_callback' => '\Drupal\arch_product\Controller\ProductViewController::title',
      ])
      ->setRequirement('product', '\d+')
      ->setRequirement('_entity_access', 'product.view');
    $route_collection->add('entity.product.canonical', $route);

    $route = (new Route('/product/{product}/delete'))
      ->addDefaults([
        '_entity_form' => 'product.delete',
        '_title' => 'Delete',
      ])
      ->setRequirement('product', '\d+')
      ->setRequirement('_entity_access', 'product.delete')
      ->setOption('_product_operation_route', TRUE);
    $route_collection->add('entity.product.delete_form', $route);

    $route = (new Route('/product/{product}/edit'))
      ->setDefault('_entity_form', 'product.edit')
      ->setRequirement('_entity_access', 'product.update')
      ->setRequirement('product', '\d+')
      ->setOption('_product_operation_route', TRUE);
    $route_collection->add('entity.product.edit_form', $route);

    return $route_collection;
  }

}
