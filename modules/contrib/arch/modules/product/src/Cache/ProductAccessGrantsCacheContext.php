<?php

namespace Drupal\arch_product\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\Cache\Context\UserCacheContextBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the product access view cache context service.
 *
 * Cache context ID: 'user.product_grants' (to vary by all operations' grants).
 * Calculated cache context ID: 'user.product_grants:%operation', e.g.
 * 'user.product_grants:view' (to vary by the view operation's grants).
 *
 * This allows for product access grants-sensitive caching when listing
 * products.
 *
 * @see arch_product_query_product_access_alter()
 * @ingroup product_access
 */
class ProductAccessGrantsCacheContext extends UserCacheContextBase implements CalculatedCacheContextInterface {

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new ProductAccessGrantsCacheContext class.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(
    AccountInterface $user,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($user);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Product access view grants', [], ['context' => 'arch_product']);
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($operation = NULL) {
    // If the current user either can bypass product access then we don't need
    // to determine the exact product grants for the current user.
    if ($this->user->hasPermission('bypass product access')) {
      return 'all';
    }

    // When no specific operation is specified, check the grants for all three
    // possible operations.
    if ($operation === NULL) {
      $result = [];
      foreach (['view', 'update', 'delete'] as $op) {
        $result[] = $this->checkProductGrants($op);
      }
      return implode('-', $result);
    }

    return $this->checkProductGrants($operation);
  }

  /**
   * Checks the product grants for the given operation.
   *
   * @param string $operation
   *   The operation to check the product grants for.
   *
   * @return string
   *   The string representation of the cache context.
   */
  protected function checkProductGrants($operation) {
    // When checking the grants for the 'view' operation and the current user
    // has a global view grant (i.e. a view grant for product ID 0) — note that
    // this is automatically the case if no product access modules exist (no
    // hook_product_grants() implementations) then we don't need to determine
    // the exact product view grants for the current user.
    if ($operation === 'view' && product_access_view_all_products($this->user)) {
      return 'view.all';
    }

    $grants = product_access_grants($operation, $this->user);
    $grants_context_parts = [];
    foreach ($grants as $realm => $gids) {
      $grants_context_parts[] = $realm . ':' . implode(',', $gids);
    }
    return $operation . '.' . implode(';', $grants_context_parts);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($operation = NULL) {
    $cacheable_metadata = new CacheableMetadata();

    if (!$this->moduleHandler->getImplementations('product_grants')) {
      return $cacheable_metadata;
    }

    // The product grants may change if the user is updated. (The max-age is set
    // to zero below, but sites may override this cache context, and change it
    // to a non-zero value. In such cases, this cache tag is needed for
    // correctness.)
    $cacheable_metadata->setCacheTags(['user:' . $this->user->id()]);

    // If the site is using product grants, this cache context can not be
    // optimized.
    return $cacheable_metadata->setCacheMaxAge(0);
  }

}
