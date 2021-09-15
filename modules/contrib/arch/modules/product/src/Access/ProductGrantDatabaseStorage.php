<?php

namespace Drupal\arch_product\Access;

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a storage handler class that handles the product grants system.
 *
 * This is used to build product query access.
 *
 * @ingroup product_access
 */
class ProductGrantDatabaseStorage implements ProductGrantDatabaseStorageInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a ProductGrantDatabaseStorage object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(
    Connection $database,
    ModuleHandlerInterface $module_handler,
    LanguageManagerInterface $language_manager
  ) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function access(ProductInterface $product, $operation, AccountInterface $account) {
    // Grants only support these operations.
    if (!in_array($operation, ['view', 'update', 'delete'])) {
      return AccessResult::neutral();
    }

    // If no module implements the hook or the product does not have an id there
    // is no point in querying the database for access grants.
    if (
      !$this->moduleHandler->getImplementations('product_grants')
      || !$product->id()
    ) {
      // Return the equivalent of the default grant, defined by
      // self::writeDefault().
      if ($operation === 'view') {
        return AccessResult::allowedIf($product->isPublished())->addCacheableDependency($product);
      }
      else {
        return AccessResult::neutral();
      }
    }

    // Check the database for potential access grants.
    $query = $this->database->select('arch_product_access');
    $query->addExpression('1');
    // Only interested for granting in the current operation.
    $query->condition('grant_' . $operation, 1, '>=');
    // Check for grants for this product and the correct langcode.
    $pids = $query->andConditionGroup()
      ->condition('pid', $product->id())
      ->condition('langcode', $product->language()->getId());
    // If the product is published, also take the default grant into account.
    // The default is saved with a product ID of 0.
    $status = $product->isPublished();
    if ($status) {
      $pids = $query->orConditionGroup()
        ->condition($pids)
        ->condition('pid', 0);
    }
    $query->condition($pids);
    $query->range(0, 1);

    $grants = static::buildGrantsQueryCondition(product_access_grants($operation, $account));

    if (count($grants) > 0) {
      $query->condition($grants);
    }

    // Only the 'view' product grant can currently be cached; the others
    // currently don't have any cacheability metadata. Hopefully, we can add
    // that in the future, which would allow this access check result to be
    // cacheable in all cases. For now, this must remain marked as uncacheable,
    // even when it is theoretically cacheable, because we don't have the
    // necessary metadata to know it for a fact.
    $set_cacheability = function (AccessResult $access_result) use ($operation) {
      $access_result->addCacheContexts(['user.product_grants:' . $operation]);
      if ($operation !== 'view') {
        $access_result->setCacheMaxAge(0);
      }
      return $access_result;
    };

    if ($query->execute()->fetchField()) {
      return $set_cacheability(AccessResult::allowed());
    }
    else {
      return $set_cacheability(AccessResult::neutral());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkAll(AccountInterface $account) {
    $query = $this->database->select('arch_product_access');
    $query->addExpression('COUNT(*)');
    $query
      ->condition('pid', 0)
      ->condition('grant_view', 1, '>=');

    $grants = static::buildGrantsQueryCondition(product_access_grants('view', $account));

    if (count($grants) > 0) {
      $query->condition($grants);
    }
    return $query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function alterQuery($query, array $tables, $op, AccountInterface $account, $base_table) {
    if (!$langcode = $query->getMetaData('langcode')) {
      $langcode = FALSE;
    }

    // Find all instances of the base table being joined -- could appear
    // more than once in the query, and could be aliased. Join each one to
    // the arch_product_access table.
    $grants = product_access_grants($op, $account);
    foreach ($tables as $palias => $tableinfo) {
      $table = $tableinfo['table'];
      if (!($table instanceof SelectInterface) && $table == $base_table) {
        // Set the subquery.
        $subquery = $this->database->select('arch_product_access', 'pa')
          ->fields('pa', ['pid']);

        // If any grant exists for the specified user, then user has access to
        // the product for the specified operation.
        $grant_conditions = static::buildGrantsQueryCondition($grants);

        // Attach conditions to the subquery for products.
        if (count($grant_conditions->conditions())) {
          $subquery->condition($grant_conditions);
        }
        $subquery->condition('pa.grant_' . $op, 1, '>=');

        // Add langcode-based filtering if this is a multilingual site.
        if ($this->languageManager->isMultilingual()) {
          // If no specific langcode to check for is given, use the grant entry
          // which is set as a fallback.
          // If a specific langcode is given, use the grant entry for it.
          if ($langcode === FALSE) {
            $subquery->condition('pa.fallback', 1, '=');
          }
          else {
            $subquery->condition('pa.langcode', $langcode, '=');
          }
        }

        $field = 'pid';
        // Now handle entities.
        $subquery->where("$palias.$field = pa.pid");

        $query->exists($subquery);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function write(ProductInterface $product, array $grants, $realm = NULL, $delete = TRUE) {
    if ($delete) {
      $query = $this->database->delete('arch_product_access')
        ->condition('pid', $product->id());
      if ($realm) {
        $query->condition('realm', [$realm, 'all'], 'IN');
      }
      $query->execute();
    }
    // Only perform work when arch_product_access modules are active.
    if (
      !empty($grants)
      && count($this->moduleHandler->getImplementations('product_grants'))
    ) {
      $query = $this->database
        ->insert('arch_product_access')
        ->fields([
          'pid',
          'langcode',
          'fallback',
          'realm',
          'gid',
          'grant_view',
          'grant_update',
          'grant_delete',
        ]);
      // If we have defined a granted langcode, use it. But if not, add a grant
      // for every language this product is translated to.
      $fallback_langcode = $product->getUntranslated()->language()->getId();
      foreach ($grants as $grant) {
        if ($realm && $realm != $grant['realm']) {
          continue;
        }
        if (isset($grant['langcode'])) {
          $grant_languages = [$grant['langcode'] => $this->languageManager->getLanguage($grant['langcode'])];
        }
        else {
          $grant_languages = $product->getTranslationLanguages(TRUE);
        }
        foreach ($grant_languages as $grant_langcode => $grant_language) {
          // Only write grants; denies are implicit.
          if ($grant['grant_view'] || $grant['grant_update'] || $grant['grant_delete']) {
            $grant['pid'] = $product->id();
            $grant['langcode'] = $grant_langcode;
            // The record with the original langcode is used as the fallback.
            if ($grant['langcode'] == $fallback_langcode) {
              $grant['fallback'] = 1;
            }
            else {
              $grant['fallback'] = 0;
            }
            $query->values($grant);
          }
        }
      }
      $query->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->database->truncate('arch_product_access')->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function writeDefault() {
    $this->database->insert('arch_product_access')
      ->fields([
        'pid' => 0,
        'realm' => 'all',
        'gid' => 0,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return $this->database->query('SELECT COUNT(*) FROM {arch_product_access}')->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteProductRecords(array $pids) {
    $this->database->delete('arch_product_access')
      ->condition('pid', $pids, 'IN')
      ->execute();
  }

  /**
   * Creates a query condition from an array of product access grants.
   *
   * @param array $product_access_grants
   *   An array of grants, as returned by product_access_grants().
   *
   * @return \Drupal\Core\Database\Query\Condition
   *   A condition object to be passed to $query->condition().
   *
   * @see product_access_grants()
   */
  protected static function buildGrantsQueryCondition(array $product_access_grants) {
    $grants = new Condition("OR");
    foreach ($product_access_grants as $realm => $gids) {
      if (!empty($gids)) {
        $and = new Condition('AND');
        $grants->condition($and
          ->condition('gid', $gids, 'IN')
          ->condition('realm', $realm)
        );
      }
    }

    return $grants;
  }

}
