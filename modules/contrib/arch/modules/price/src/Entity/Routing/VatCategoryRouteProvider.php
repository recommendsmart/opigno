<?php

namespace Drupal\arch_price\Entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * VAT category route provider.
 *
 * @package Drupal\arch_price\Entity\Routing
 */
class VatCategoryRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getCollectionRoute($entity_type)) {
      $route->setRequirement('_permission', 'access vat category overview+administer prices');
      return $route;
    }
  }

}
