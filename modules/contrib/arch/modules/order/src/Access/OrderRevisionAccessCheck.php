<?php

namespace Drupal\arch_order\Access;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an access checker for order revisions.
 *
 * @ingroup order_access
 */
class OrderRevisionAccessCheck implements AccessInterface {

  /**
   * The order storage.
   *
   * @var \Drupal\arch_order\Entity\Storage\OrderStorageInterface
   */
  protected $orderStorage;

  /**
   * The order access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $orderAccess;

  /**
   * A static cache of access checks.
   *
   * @var array
   */
  protected $access = [];

  /**
   * Constructs a new OrderRevisionAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->orderStorage = $entity_type_manager->getStorage('order');
    $this->orderAccess = $entity_type_manager->getAccessControlHandler('order');
  }

  /**
   * Checks routing access for the order revision.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param int $order_revision
   *   (optional) The order revision ID. If not specified, but $order is,
   *   access is checked for that object's revision.
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   (optional) A order object. Used for checking access to a order's
   *   default revision when $order_revision is unspecified. Ignored when
   *   $order_revision is specified. If neither $order_revision nor $order
   *   are specified, then access is denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, $order_revision = NULL, OrderInterface $order = NULL) {
    if ($order_revision) {
      $order = $this->orderStorage->loadRevision($order_revision);
    }
    $operation = $route->getRequirement('_access_order_revision');
    return AccessResult::allowedIf($order && $this->checkAccess($order, $account, $operation))->cachePerPermissions()->addCacheableDependency($order);
  }

  /**
   * Checks order revision access.
   *
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   The order to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   * @param string $op
   *   (optional) The specific operation being checked. Defaults to 'view'.
   *
   * @return bool
   *   TRUE if the operation may be performed, FALSE otherwise.
   */
  public function checkAccess(OrderInterface $order, AccountInterface $account, $op = 'view') {
    $map = [
      'view' => 'view all order revisions',
      'update' => 'revert all order revisions',
      'delete' => 'delete all order revisions',
    ];

    if (!$order || !isset($map[$op])) {
      // If there was no order to check against, or the $op was not one of the
      // supported ones, we return access denied.
      return FALSE;
    }

    // Statically cache access by revision ID, language code, user account ID,
    // and operation.
    $langcode = $order->language()->getId();
    $cid = $order->getRevisionId() . ':' . $langcode . ':' . $account->id() . ':' . $op;

    if (!isset($this->access[$cid])) {
      // Perform basic permission checks first.
      if (
        !$account->hasPermission($map[$op])
        && !$account->hasPermission('administer orders')
      ) {
        $this->access[$cid] = FALSE;
        return FALSE;
      }

      // There should be at least two revisions. If the vid of the given order
      // and the vid of the default revision differ, then we already have two
      // different revisions so there is no need for a separate database check.
      // Also, if you try to revert to or delete the default revision, that's
      // not good.
      if (
        $order->isDefaultRevision()
        && (
          $op == 'update'
          || $op == 'delete'
        )
      ) {
        $this->access[$cid] = FALSE;
      }
      elseif ($account->hasPermission('administer orders')) {
        $this->access[$cid] = TRUE;
      }
      else {
        // First check the access to the default revision and finally, if the
        // orders passed in is not the default revision then access to that,
        // too.
        $this->access[$cid] = $this->orderAccess->access($this->orderStorage->load($order->id()), $op, $account) && ($order->isDefaultRevision() || $this->orderAccess->access($order, $op, $account));
      }
    }

    return $this->access[$cid];
  }

}
