<?php

namespace Drupal\arch_order\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for orders.
 */
class OrderRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $route_collection = new RouteCollection();
    $route = (new Route('/order/{order}'))
      ->addDefaults([
        '_controller' => '\Drupal\arch_order\Controller\OrderViewController::view',
        '_title_callback' => '\Drupal\arch_order\Controller\OrderViewController::title',
      ])
      ->setRequirement('order', '\d+')
      ->setRequirement('_entity_access', 'order.view');
    $route_collection->add('entity.order.canonical', $route);

    $route = (new Route('/order/{order}/delete'))
      ->addDefaults([
        '_entity_form' => 'order.delete',
        '_title' => 'Delete',
      ])
      ->setRequirement('order', '\d+')
      ->setRequirement('_entity_access', 'order.delete')
      ->setOption('_order_operation_route', TRUE);
    $route_collection->add('entity.order.delete_form', $route);

    $route = (new Route('/order/{order}/edit'))
      ->setDefault('_entity_form', 'order.edit')
      ->setRequirement('_entity_access', 'order.update')
      ->setRequirement('order', '\d+')
      ->setOption('_order_operation_route', TRUE);
    $route_collection->add('entity.order.edit_form', $route);

    return $route_collection;
  }

}
