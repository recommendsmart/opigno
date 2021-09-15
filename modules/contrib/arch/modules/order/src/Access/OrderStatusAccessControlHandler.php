<?php

namespace Drupal\arch_order\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the order status entity type.
 *
 * @see \Drupal\arch_order\Entity\OrderStatus
 */
class OrderStatusAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return parent::checkAccess($entity, $operation, $account);

      case 'update':
        /** @var \Drupal\arch_order\Entity\OrderStatusInterface $entity */
        return AccessResult::allowedIf($account->hasPermission('administer order status configuration'))->addCacheableDependency($entity)
          ->andIf(parent::checkAccess($entity, $operation, $account));

      case 'delete':
        /** @var \Drupal\arch_order\Entity\OrderStatusInterface $entity */
        return AccessResult::allowedIf($account->hasPermission('administer order status configuration'))->addCacheableDependency($entity)
          ->andIf(parent::checkAccess($entity, $operation, $account));

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

}
