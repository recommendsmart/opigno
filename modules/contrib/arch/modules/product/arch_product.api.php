<?php
/**
 * @file
 * Hooks specific to the Arch module.
 */

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * @addtogroup hooks
 * @{
 */

// @codingStandardsIgnoreStart Drupal.Commenting.DocComment.TagsNotGrouped
/**
 * Inform the product access system what permissions the user has.
 *
 * This hook is for implementation by product access modules. In this hook,
 * the module grants a user different "grant IDs" within one or more
 * "realms". In hook_product_access_records(), the realms and grant IDs are
 * associated with permission to view, edit, and delete individual products.
 *
 * The realms and grant IDs can be arbitrarily defined by your product access
 * module; it is common to use role IDs as grant IDs, but that is not required.
 * Your module could instead maintain its own list of users, where each list has
 * an ID. In that case, the return value of this hook would be an array of the
 * list IDs that this user is a member of.
 *
 * A product access module may implement as many realms as necessary to properly
 * define the access privileges for the products. Note that the system makes no
 * distinction between published and unpublished products. It is the module's
 * responsibility to provide appropriate realms to limit access to unpublished
 * content.
 *
 * Product access records are stored in the {arch_product_access} table and
 * define which grants are required to access a product. There is a special case
 * for the view operation -- a record with product ID 0 corresponds to a
 * "view all" grant for the realm and grant ID of that record. If there are no
 * product access modules enabled, the core product module adds a product ID 0
 * record for realm 'all'. Product access modules can also grant "view all"
 * permission on their custom realms; for example, a module could create a
 * record in {arch_product_access} with:
 * @code
 * $record = array(
 *   'pid' => 0,
 *   'gid' => 888,
 *   'realm' => 'example_realm',
 *   'grant_view' => 1,
 *   'grant_update' => 0,
 *   'grant_delete' => 0,
 * );
 * db_insert('arch_product_access')->fields($record)->execute();
 * @endcode
 * And then in its hook_product_grants() implementation, it would need to
 * return:
 * @code
 * if ($op == 'view') {
 *   $grants['example_realm'] = array(888);
 * }
 * @endcode
 * If you decide to do this, be aware that the product_access_rebuild() function
 * will erase any product ID 0 entry when it is called, so you will need to make
 * sure to restore your {arch_product_access} record after
 * product_access_rebuild() is called.
 *
 * For a detailed example, see product_access_example.module.
 *
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account object whose grants are requested.
 * @param string $op
 *   The product operation to be performed, such as 'view', 'update', or
 *   'delete'.
 *
 * @return array
 *   An array whose keys are "realms" of grants, and whose values are arrays of
 *   the grant IDs within this realm that this user is being granted.
 *
 * @see product_access_view_all_products()
 * @see product_access_rebuild()
 * @ingroup product_access
 */
function hook_product_grants(AccountInterface $account, $op) {
  if ($account->hasPermission('access private product')) {
    $grants['example'] = [1];
  }
  if ($account->id()) {
    $grants['example_author'] = [$account->id()];
  }
  return $grants;
}
// @codingStandardsIgnoreEnd Drupal.Commenting.DocComment.TagsNotGrouped

/**
 * Set permissions for a product to be written to the database.
 *
 * When a product is saved, a module implementing hook_product_access_records()
 * will be asked if it is interested in the access permissions for a product. If
 * it is interested, it must respond with an array of permissions arrays for
 * that product.
 *
 * Product access grants apply regardless of the published or unpublished status
 * of the product. Implementations must make sure not to grant access to
 * unpublished products if they don't want to change the standard access control
 * behavior. Your module may need to create a separate access realm to handle
 * access to unpublished products.
 *
 * Note that the grant values in the return value from your hook must be
 * integers and not boolean TRUE and FALSE.
 *
 * Each permissions item in the array is an array with the following elements:
 * - 'realm': The name of a realm that the module has defined in
 *   hook_product_grants().
 * - 'gid': A 'grant ID' from hook_product_grants().
 * - 'grant_view': If set to 1 a user that has been identified as a member
 *   of this gid within this realm can view this product. This should usually be
 *   set to $product->isPublished(). Failure to do so may expose unpublished
 *   content to some users.
 * - 'grant_update': If set to 1 a user that has been identified as a member
 *   of this gid within this realm can edit this product.
 * - 'grant_delete': If set to 1 a user that has been identified as a member
 *   of this gid within this realm can delete this product.
 * - langcode: (optional) The language code of a specific translation of the
 *   product, if any. Modules may add this key to grant different access to
 *   different translations of a product, such that (e.g.) a particular group is
 *   granted access to edit the Catalan version of the product, but not the
 *   Hungarian version. If no value is provided, the langcode is set
 *   automatically from the $product parameter and the product's original
 *   language (if specified) is used as a fallback. Only specify multiple grant
 *   records with different languages for a product if the site has those
 *   languages configured.
 *
 * A "deny all" grant may be used to deny all access to a particular product or
 * product translation:
 * @code
 * $grants[] = array(
 *   'realm' => 'all',
 *   'gid' => 0,
 *   'grant_view' => 0,
 *   'grant_update' => 0,
 *   'grant_delete' => 0,
 *   'langcode' => 'ca',
 * );
 * @endcode
 * Note that another module product access module could override this by
 * granting access to one or more products, since grants are additive. To
 * enforce that access is denied in a particular case, use
 * hook_product_access_records_alter(). Also note that a deny all is not written
 * to the database; denies are implicit.
 *
 * @param \Drupal\arch_product\Entity\ProductInterface $product
 *   The product that has just been saved.
 *
 * @return array|null
 *   An array of grants as defined above.
 *
 * @see hook_product_access_records_alter()
 * @ingroup product_access
 */
function hook_product_access_records(ProductInterface $product) {
  // We only care about the product if it has been marked private. If not, it is
  // treated just like any other product and we completely ignore it.
  if (empty($product->private->value)) {
    return NULL;
  }

  $grants = [];
  // Only published Catalan translations of private products should be
  // viewable to all users. If we fail to check $product->isPublished(), all
  // users would be able to view an unpublished product.
  if ($product->isPublished()) {
    $grants[] = [
      'realm' => 'example',
      'gid' => 1,
      'grant_view' => 1,
      'grant_update' => 0,
      'grant_delete' => 0,
      'langcode' => 'ca',
    ];
  }
  // For the example_author array, the GID is equivalent to a UID, which
  // means there are many groups of just 1 user.
  // Note that an author can always view their products, even if they
  // have status unpublished.
  if ($product->getOwnerId()) {
    $grants[] = [
      'realm' => 'example_author',
      'gid' => $product->getOwnerId(),
      'grant_view' => 1,
      'grant_update' => 1,
      'grant_delete' => 1,
      'langcode' => 'ca',
    ];
  }

  return $grants;
}

/**
 * Alter permissions for a product before it is written to the database.
 *
 * Product access modules establish rules for user access to content. Product
 * access records are stored in the {arch_product_access} table and define which
 * permissions are required to access a product. This hook is invoked after
 * product access modules returned their requirements via
 * hook_product_access_records(); doing so allows modules to modify the $grants
 * array by reference before it is stored, so custom or advanced business logic
 * can be applied.
 *
 * Upon viewing, editing or deleting a product, hook_product_grants() builds a
 * permissions array that is compared against the stored access records. The
 * user must have one or more matching permissions in order to complete the
 * requested operation.
 *
 * A module may deny all access to a product by setting $grants to an empty
 * array.
 *
 * The preferred use of this hook is in a module that bridges multiple product
 * access modules with a configurable behavior, as shown in the example with the
 * 'is_preview' field.
 *
 * @param array $grants
 *   The $grants array returned by hook_product_access_records().
 * @param \Drupal\arch_product\Entity\ProductInterface $product
 *   The product for which the grants were acquired.
 *
 * @see hook_product_access_records()
 * @see hook_product_grants()
 * @see hook_product_grants_alter()
 * @ingroup product_access
 */
function hook_product_access_records_alter(array &$grants, ProductInterface $product) {
  // Our module allows editors to mark specific articles with the 'is_preview'
  // field. If the product being saved has a TRUE value for that field, then
  // only our grants are retained, and other grants are removed. Doing so
  // ensures that our rules are enforced no matter what priority other grants
  // are given.
  if ($product->is_preview) {
    // Our module grants are set in $grants['example'].
    $temp = $grants['example'];
    // Now remove all module grants but our own.
    $grants = ['example' => $temp];
  }
}

/**
 * Alter user access rules when trying to view, edit or delete a product.
 *
 * Product access modules establish rules for user access to content.
 * hook_product_grants() defines permissions for a user to view, edit or delete
 * products by building a $grants array that indicates the permissions assigned
 * to the user by each product access module. This hook is called to allow
 * modules to modify the $grants array by reference, so the interaction of
 * multiple product access modules can be altered or advanced business logic can
 * be applied.
 *
 * The resulting grants are then checked against the records stored in the
 * {arch_product_access} table to determine if the operation may be completed.
 *
 * A module may deny all access to a user by setting $grants to an empty array.
 *
 * Developers may use this hook to either add additional grants to a user or to
 * remove existing grants. These rules are typically based on either the
 * permissions assigned to a user role, or specific attributes of a user
 * account.
 *
 * @param array $grants
 *   The $grants array returned by hook_product_grants().
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account requesting access to content.
 * @param string $op
 *   The operation being performed, 'view', 'update' or 'delete'.
 *
 * @see hook_product_grants()
 * @see hook_product_access_records()
 * @see hook_product_access_records_alter()
 * @ingroup product_access
 */
function hook_product_grants_alter(array &$grants, AccountInterface $account, $op) {
  // Our sample module never allows certain roles to edit or delete
  // content. Since some other product access modules might allow this
  // permission, we expressly remove it by returning an empty $grants
  // array for roles specified in our variable setting.
  //
  // Get our list of banned roles.
  $restricted = \Drupal::config('example.settings')->get('restricted_roles');

  if ($op != 'view' && !empty($restricted)) {
    // Now check the roles for this account against the restrictions.
    foreach ($account->getRoles() as $rid) {
      if (in_array($rid, $restricted)) {
        $grants = [];
      }
    }
  }
}

/**
 * Controls access to a product.
 *
 * Modules may implement this hook if they want to have a say in whether or not
 * a given user has access to perform a given operation on a product.
 *
 * The administrative account (user ID #1) always passes any access check, so
 * this hook is not called in that case. Users with the "bypass product access"
 * permission may always view and edit content through the administrative
 * interface.
 *
 * Note that not all modules will want to influence access on all product types.
 * If your module does not want to explicitly allow or forbid access, return an
 * AccessResultInterface object with neither isAllowed() nor isForbidden()
 * equaling TRUE. Blindly returning an object with isForbidden() equaling TRUE
 * will break other product access modules.
 *
 * Also note that this function isn't called for product listings (e.g., RSS
 * feeds, the default home page at path 'product', a recent content block, etc.)
 * See @link product_access Product access rights @endlink for a full
 * explanation.
 *
 * @param \Drupal\arch_product\Entity\ProductInterface|string $product
 *   Either a product entity or the machine name of the content type on which to
 *   perform the access check.
 * @param string $op
 *   The operation to be performed. Possible values:
 *   - "create"
 *   - "delete"
 *   - "update"
 *   - "view".
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The user object to perform the access check operation on.
 *
 * @return \Drupal\Core\Access\AccessResultInterface
 *   The access result.
 *
 * @ingroup product_access
 */
function hook_product_access(ProductInterface $product, $op, AccountInterface $account) {
  $type = $product->bundle();

  switch ($op) {
    case 'create':
      return AccessResult::allowedIfHasPermission($account, 'create ' . $type . ' product');

    case 'update':
      if ($account->hasPermission('edit any ' . $type . ' product', $account)) {
        return AccessResult::allowed()->cachePerPermissions();
      }
      else {
        return AccessResult::allowedIf($account->hasPermission('edit own ' . $type . ' product', $account) && ($account->id() == $product->getOwnerId()))->cachePerPermissions()->cachePerUser()->addCacheableDependency($product);
      }

    case 'delete':
      if ($account->hasPermission('delete any ' . $type . ' product', $account)) {
        return AccessResult::allowed()->cachePerPermissions();
      }
      else {
        return AccessResult::allowedIf($account->hasPermission('delete own ' . $type . ' product', $account) && ($account->id() == $product->getOwnerId()))->cachePerPermissions()->cachePerUser()->addCacheableDependency($product);
      }

    default:
      // No opinion.
      return AccessResult::neutral();
  }
}

/**
 * Act on a product being displayed as a search result.
 *
 * This hook is invoked from the product search plugin during search execution,
 * after loading and rendering the product.
 *
 * @param \Drupal\arch_product\Entity\ProductInterface $product
 *   The product being displayed in a search result.
 *
 * @return array
 *   Extra information to be displayed with search result. This information
 *   should be presented as an associative array. It will be concatenated with
 *   the post information (last updated, author) in the default search result
 *   theming.
 *
 * @see template_preprocess_search_result()
 * @see search-result.html.twig
 *
 * @ingroup entity_crud
 */
function hook_product_search_result(ProductInterface $product) {
  $rating = \Drupal::database()
    ->query('SELECT SUM(points) FROM {my_rating} WHERE pid = :pid', ['pid' => $product->id()])
    ->fetchField();
  return [
    'rating' => \Drupal::translation()->formatPlural($rating, '1 point', '@count points'),
  ];
}

/**
 * Act on a product being indexed for searching.
 *
 * This hook is invoked during search indexing, after loading, and after the
 * result of rendering is added as $product->rendered to the product object.
 *
 * @param \Drupal\arch_product\Entity\ProductInterface $product
 *   The product being indexed.
 *
 * @return string
 *   Additional product information to be indexed.
 *
 * @ingroup entity_crud
 */
function hook_product_update_index(ProductInterface $product) {
  $text = '';
  $ratings = \Drupal::database()
    ->query('SELECT title, description FROM {my_ratings} WHERE pid = :pid', [':pid' => $product->id()])
    ->fetchAll();
  foreach ($ratings as $rating) {
    $text .= '<h2>' . Html::escape($rating->title) . '</h2>' . Xss::filter($rating->description);
  }
  return $text;
}

/**
 * Alter the links of a product.
 *
 * @param array &$links
 *   A renderable array representing the product links.
 * @param \Drupal\arch_product\Entity\ProductInterface $entity
 *   The product being rendered.
 * @param array &$context
 *   Various aspects of the context in which the product links are going to be
 *   displayed, with the following keys:
 *   - 'view_mode': the view mode in which the product is being viewed
 *   - 'langcode': the language in which the product is being viewed.
 *
 * @see \Drupal\arch_product\Entity\Builder\ProductViewBuilder::renderLinks()
 * @see \Drupal\arch_product\Entity\Builder\ProductViewBuilder::buildLinks()
 * @see entity_crud
 */
function hook_product_links_alter(array &$links, ProductInterface $entity, array &$context) {
  $links['mymodule'] = [
    '#theme' => 'links__product__mymodule',
    '#attributes' => ['class' => ['links', 'inline']],
    '#links' => [
      'product-report' => [
        'title' => t('Report'),
        'url' => Url::fromRoute('product_test.report', ['product' => $entity->id()], ['query' => ['token' => \Drupal::getContainer()->get('csrf_token')->get("product/{$entity->id()}/report")]]),
      ],
    ],
  ];
}

/**
 * Controls access to a product purchase.
 *
 * @param \Drupal\arch_product\Entity\ProductInterface $product
 *   Product.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   Customer.
 *
 * @return \Drupal\Core\Access\AccessResult
 *   The access result.
 */
function hook_product_available_for_sell(ProductInterface $product, AccountInterface $account) {
  return AccessResult::allowedIf($account->isAuthenticated());
}

/**
 * Alter product access to purchase.
 *
 * @param \Drupal\Core\Access\AccessResult $available_result
 *   Access result.
 * @param \Drupal\arch_product\Entity\ProductInterface $product
 *   Product.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   Customer.
 */
function hook_product_available_for_sell_alter(AccessResult $available_result, ProductInterface $product, AccountInterface $account) {
  // @todo Add example implementation.
}

/**
 * Alter availability options.
 *
 * @param array $options
 *   Availability options.
 */
function hook_arch_product_availability_options_alter(array &$options) {
  // @todo Add example implementation.
}

/**
 * @} End of "addtogroup hooks".
 */
