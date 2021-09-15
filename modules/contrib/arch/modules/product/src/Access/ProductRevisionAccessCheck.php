<?php

namespace Drupal\arch_product\Access;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an access checker for product revisions.
 *
 * @ingroup product_access
 */
class ProductRevisionAccessCheck implements AccessInterface {

  /**
   * The product storage.
   *
   * @var \Drupal\arch_product\Entity\Storage\ProductStorageInterface
   */
  protected $productStorage;

  /**
   * The product access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $productAccess;

  /**
   * A static cache of access checks.
   *
   * @var array
   */
  protected $access = [];

  /**
   * Constructs a new ProductRevisionAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->productStorage = $entity_type_manager->getStorage('product');
    $this->productAccess = $entity_type_manager->getAccessControlHandler('product');
  }

  /**
   * Checks routing access for the product revision.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param int $product_revision
   *   (optional) The product revision ID. If not specified, but $product is,
   *   access is checked for that object's revision.
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   (optional) A product object. Used for checking access to a product's
   *   default revision when $product_revision is unspecified. Ignored when
   *   $product_revision is specified. If neither $product_revision nor $product
   *   are specified, then access is denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, $product_revision = NULL, ProductInterface $product = NULL) {
    if ($product_revision) {
      $product = $this->productStorage->loadRevision($product_revision);
    }
    $operation = $route->getRequirement('_access_product_revision');
    return AccessResult::allowedIf($product && $this->checkAccess($product, $account, $operation))->cachePerPermissions()->addCacheableDependency($product);
  }

  /**
   * Checks product revision access.
   *
   * @param \Drupal\arch_product\Entity\ProductInterface $product
   *   The product to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   * @param string $op
   *   (optional) The specific operation being checked. Defaults to 'view'.
   *
   * @return bool
   *   TRUE if the operation may be performed, FALSE otherwise.
   */
  public function checkAccess(ProductInterface $product, AccountInterface $account, $op = 'view') {
    $map = [
      'view' => 'view all product revisions',
      'update' => 'revert all product revisions',
      'delete' => 'delete all product revisions',
    ];
    $bundle = $product->bundle();
    $type_map = [
      'view' => "view $bundle product revisions",
      'update' => "revert $bundle product revisions",
      'delete' => "delete $bundle product revisions",
    ];

    if (!$product || !isset($map[$op]) || !isset($type_map[$op])) {
      // If there was no product to check against, or the $op was not one of the
      // supported ones, we return access denied.
      return FALSE;
    }

    // Statically cache access by revision ID, language code, user account ID,
    // and operation.
    $langcode = $product->language()->getId();
    $cid = $product->getRevisionId() . ':' . $langcode . ':' . $account->id() . ':' . $op;

    if (!isset($this->access[$cid])) {
      // Perform basic permission checks first.
      if (
        !$account->hasPermission($map[$op])
        && !$account->hasPermission($type_map[$op])
        && !$account->hasPermission('administer products')
      ) {
        $this->access[$cid] = FALSE;
        return FALSE;
      }

      // There should be at least two revisions. If the vid of the given product
      // and the vid of the default revision differ, then we already have two
      // different revisions so there is no need for a separate database check.
      // Also, if you try to revert to or delete the default revision, that's
      // not good.
      if (
        $product->isDefaultRevision()
        && (
          $this->productStorage->countDefaultLanguageRevisions($product) == 1
          || $op == 'update'
          || $op == 'delete'
        )
      ) {
        $this->access[$cid] = FALSE;
      }
      elseif ($account->hasPermission('administer products')) {
        $this->access[$cid] = TRUE;
      }
      else {
        // First check the access to the default revision and finally, if the
        // products passed in is not the default revision then access to that,
        // too.
        $this->access[$cid] = $this->productAccess->access($this->productStorage->load($product->id()), $op, $account) && ($product->isDefaultRevision() || $this->productAccess->access($product, $op, $account));
      }
    }

    return $this->access[$cid];
  }

}
