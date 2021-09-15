<?php

namespace Drupal\arch_price\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the vat category entity type.
 *
 * @see \Drupal\arch_price\Entity\VatCategory
 */
class VatCategoryAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(
    EntityInterface $entity,
    $operation,
    AccountInterface $account = NULL,
    $return_as_object = FALSE
  ) {
    $account = $this->prepareUser($account);

    if ($operation === 'delete' && $entity->isLocked()) {
      $result = AccessResult::forbidden('The VAT category config entity is locked.')
        ->addCacheableDependency($entity);
      return $return_as_object ? $result : $result->isAllowed();
    }

    if ($account->hasPermission('administer prices')) {
      $result = AccessResult::allowed()->cachePerPermissions();
      return $return_as_object ? $result : $result->isAllowed();
    }

    return parent::access($entity, $operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\arch_price\Entity\VatCategoryInterface $entity */
    if ($operation == 'view') {
      return AccessResult::allowedIfHasPermissions(
        $account,
        ['administer prices'],
        'OR'
      );
    }
    elseif ($operation == 'delete') {
      if ($entity->isLocked()) {
        return AccessResult::forbidden('The VAT category config entity is locked.')
          ->addCacheableDependency($entity);
      }
      else {
        return parent::checkAccess($entity, $operation, $account)
          ->addCacheableDependency($entity);
      }
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
