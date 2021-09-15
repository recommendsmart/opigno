<?php

namespace Drupal\arch_product\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the product type entity type.
 *
 * @see \Drupal\arch_product\Entity\ProductType
 */
class ProductTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'access product');

      case 'delete':
        if ($entity->isLocked()) {
          return AccessResult::forbidden()->addCacheableDependency($entity);
        }
        else {
          return parent::checkAccess($entity, $operation, $account)->addCacheableDependency($entity);
        }
        break;

      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
