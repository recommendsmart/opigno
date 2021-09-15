<?php

namespace Drupal\arch_stock\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Warehouse route provider.
 *
 * @package Drupal\arch_stock\Entity\Routing
 */
class WarehouseRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getCollectionRoute($entity_type)) {
      $route->setRequirement('_permission', 'access warehouse overview+administer stock');
      return $route;
    }
  }

}
