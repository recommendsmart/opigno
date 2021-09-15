<?php

namespace Drupal\arch_addressbook\Access;

use Drupal\arch_addressbook\AddressbookitemInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an access checker for addressbookitem revisions.
 *
 * @ingroup addressbookitem_access
 */
class AddressbookRevisionAccessCheck implements AccessInterface {

  /**
   * The addressbookitem storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The addressbookitem access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $addressbookitemAccess;

  /**
   * A static cache of access checks.
   *
   * @var array
   */
  protected $access = [];

  /**
   * Constructs a new AddressbookRevisionAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityStorage = $entity_type_manager->getStorage('addressbookitem');

    // @todo Implement this.
    // @codingStandardsIgnoreStart
    // $this->addressbookitemAccess = $entity_type_manager->getAccessControlHandler('addressbookitem');
    // @codingStandardsIgnoreEnd
  }

  // @codingStandardsIgnoreStart
  /**
   * Checks routing access for the addressbookitem revision.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param int $addressbookitem_revision
   *   (optional) The addressbookitem revision ID. If not specified, but $addressbookitem is,
   *   access is checked for that object's revision.
   * @param \Drupal\arch_addressbook\AddressbookitemInterface $addressbookitem
   *   (optional) An addressbookitem object. Used for checking access to a addressbookitem's
   *   default revision when $addressbookitem_revision is unspecified. Ignored when
   *   $addressbookitem_revision is specified. If neither $addressbookitem_revision nor $addressbookitem
   *   are specified, then access is denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, $addressbookitem_revision = NULL, AddressbookitemInterface $addressbookitem = NULL) {
    if ($addressbookitem_revision) {
      $addressbookitem = $this->entityStorage->loadRevision($addressbookitem_revision);
    }
    $operation = $route->getRequirement('_access_addressbookitem_revision');
    return AccessResult::allowedIf($addressbookitem && $this->checkAccess($addressbookitem, $account, $operation))->cachePerPermissions()->addCacheableDependency($addressbookitem);
  }
  // @codingStandardsIgnoreEnd

  /**
   * Checks addressbookitem revision access.
   *
   * @param \Drupal\arch_addressbook\AddressbookitemInterface $addressbookitem
   *   The addressbookitem to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   * @param string $op
   *   (optional) The specific operation being checked. Defaults to 'view'.
   *
   * @return bool
   *   TRUE if the operation may be performed, FALSE otherwise.
   */
  public function checkAccess(AddressbookitemInterface $addressbookitem, AccountInterface $account, $op = 'view') {
    $map = [
      'view' => 'view all addressbookitem revisions',
      'update' => 'revert all addressbookitem revisions',
      'delete' => 'delete all addressbookitem revisions',
    ];

    if (!$addressbookitem || !isset($map[$op])) {
      // If there was no addressbookitem to check against, or the $op was not
      // one of the supported ones, we return access denied.
      return FALSE;
    }

    // Statically cache access by revision ID, language code, user account ID,
    // and operation.
    $langcode = $addressbookitem->language()->getId();
    $cid = $addressbookitem->getRevisionId() . ':' . $langcode . ':' . $account->id() . ':' . $op;

    if (!isset($this->access[$cid])) {
      // Perform basic permission checks first.
      if (
        !$account->hasPermission($map[$op])
        && !$account->hasPermission('administer addressbookitems')
      ) {
        $this->access[$cid] = FALSE;
        return FALSE;
      }

      // @codingStandardsIgnoreStart
      // There should be at least two revisions.
      // If the vid of the given addressbookitem and the vid of the default
      // revision differ, then we already have two different revisions so there
      // is no need for a separate database check. Also, if you try to revert
      // to or delete the default revision, that's not good.
      // @codingStandardsIgnoreEnd
      if (
        $addressbookitem->isDefaultRevision()
        && (
          $op == 'update'
          || $op == 'delete'
        )
      ) {
        $this->access[$cid] = FALSE;
      }
      elseif ($account->hasPermission('administer addressbookitems')) {
        $this->access[$cid] = TRUE;
      }
      else {
        // @codingStandardsIgnoreStart
        // First check the access to the default revision and finally, if the
        // addressbookitems passed in is not the default revision then access to that,
        // too.
        // @todo Implement addressbookitemAccess's class!
        // $this->access[$cid] = $this->addressbookitemAccess->access($this->entityStorage->load($addressbookitem->id()), $op, $account) && ($addressbookitem->isDefaultRevision() || $this->addressbookitemAccess->access($addressbookitem, $op, $account));
        // @codingStandardsIgnoreEnd
        $this->access[$cid] = FALSE;
      }
    }

    return $this->access[$cid];
  }

}
