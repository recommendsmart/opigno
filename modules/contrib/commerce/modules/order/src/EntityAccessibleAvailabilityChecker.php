<?php

namespace Drupal\commerce_order;

use Drupal\commerce\Context;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Determines availability based on the purchased entity access.
 */
final class EntityAccessibleAvailabilityChecker implements AvailabilityCheckerInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(OrderItemInterface $order_item) {
    return $order_item->hasPurchasedEntity() && $order_item->getPurchasedEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function check(OrderItemInterface $order_item, Context $context) {
    $purchasable_entity = $order_item->getPurchasedEntity();
    // If the purchasable entity is publishable, immediately return false if
    // it is unpublished and skip entity access checks for performance.
    if ($purchasable_entity instanceof EntityPublishedInterface && $purchasable_entity->isPublished() === FALSE) {
      return AvailabilityResult::unavailable();
    }
    if (!$purchasable_entity->access('view', $context->getCustomer())) {
      return AvailabilityResult::unavailable();
    }

    return AvailabilityResult::neutral();
  }

}
