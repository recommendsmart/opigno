<?php

namespace Drupal\expose_actions\Plugin\Menu\LocalAction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

class ExposeAction extends LocalActionDefault {

  public function getRouteParameters(RouteMatchInterface $route_match) {
    $parameters = parent::getRouteParameters($route_match);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    foreach ($route_match->getParameters()->all() as $paramater) {
      if ($paramater instanceof EntityInterface) {
        $parameters['entity_type'] = $paramater->getEntityTypeId();
        $parameters['entity_id'] = $paramater->id();
      }
    }
    return $parameters;
  }

}
