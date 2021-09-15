<?php

namespace Drupal\arch_order\Access;

use Drupal\arch_order\Entity\OrderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a storage handler class that handles the order grants system.
 *
 * This is used to build order query access.
 *
 * @ingroup order_access
 */
class OrderGrantDatabaseStorage implements OrderGrantDatabaseStorageInterface {

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
   * Constructs a OrderGrantDatabaseStorage object.
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
  public function access(OrderInterface $order, $operation, AccountInterface $account) {
    $operations = ['view', 'update'];
    $orderIsMine = $order->getOwnerId() == $account->id();

    // Grants only support these operations.
    if (!in_array($operation, $operations)) {
      return AccessResult::neutral();
    }

    if (
      $account->hasPermission('bypass order access')
      || $account->hasPermission('administer orders')
    ) {
      return AccessResult::allowed()->addCacheableDependency($order);
    }

    // If no module implements the hook or the order does not have an id there
    // is no point in querying the database for access grants.
    if (
      !$this->moduleHandler->getImplementations('order_grants')
      || !$order->id()
    ) {
      // Return the equivalent of the default grant, defined by
      // self::writeDefault().
      if ($operation === 'view') {
        if ($orderIsMine) {
          return AccessResult::allowedIfHasPermission($account, 'view order')->addCacheableDependency($order);
        }
        else {
          return AccessResult::allowedIfHasPermission($account, 'view any order')->addCacheableDependency($order);
        }
      }
      else {
        return AccessResult::neutral();
      }
    }

    // Check the database for potential access grants.
    $query = $this->database->select('arch_order_access');
    $query->addExpression('1');
    // Only interested for granting in the current operation.
    $query->condition('grant_' . $operation, 1, '>=');
    // Check for grants for this order and the correct langcode.
    $oids = $query->andConditionGroup()
      ->condition('oid', $order->id())
      ->condition('langcode', $order->language()->getId());
    $query->condition($oids);
    $query->range(0, 1);

    $grants = static::buildGrantsQueryCondition(order_access_grants($operation, $account));

    if (count($grants) > 0) {
      $query->condition($grants);
    }

    // Only the 'view' order grant can currently be cached; the others
    // currently don't have any cacheability metadata. Hopefully, we can add
    // that in the future, which would allow this access check result to be
    // cacheable in all cases. For now, this must remain marked as uncacheable,
    // even when it is theoretically cacheable, because we don't have the
    // necessary metadata to know it for a fact.
    $set_cacheability = function (AccessResult $access_result) use ($operation) {
      $access_result->addCacheContexts(['user.order_grants:' . $operation]);
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
    $query = $this->database->select('arch_order_access');
    $query->addExpression('COUNT(*)');
    $query
      ->condition('oid', 0)
      ->condition('grant_view', 1, '>=');

    $grants = static::buildGrantsQueryCondition(order_access_grants('view', $account));

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
    // the arch_order_access table.
    $grants = order_access_grants($op, $account);
    foreach ($tables as $palias => $tableinfo) {
      $table = $tableinfo['table'];
      if (!($table instanceof SelectInterface) && $table == $base_table) {
        // Set the subquery.
        $subquery = $this->database->select('arch_order_access', 'oa')
          ->fields('oa', ['oid']);

        // If any grant exists for the specified user, then user has access to
        // the order for the specified operation.
        $grant_conditions = static::buildGrantsQueryCondition($grants);

        // Attach conditions to the subquery for orders.
        if (count($grant_conditions->conditions())) {
          $subquery->condition($grant_conditions);
        }
        $subquery->condition('oa.grant_' . $op, 1, '>=');

        // Add langcode-based filtering if this is a multilingual site.
        if ($this->languageManager->isMultilingual()) {
          // If no specific langcode to check for is given, use the grant entry
          // which is set as a fallback.
          // If a specific langcode is given, use the grant entry for it.
          if ($langcode === FALSE) {
            $subquery->condition('oa.fallback', 1, '=');
          }
          else {
            $subquery->condition('oa.langcode', $langcode, '=');
          }
        }

        $field = 'oid';
        // Now handle entities.
        $subquery->where("$palias.$field = pa.oid");

        $query->exists($subquery);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function write(OrderInterface $order, array $grants, $realm = NULL, $delete = TRUE) {
    if ($delete) {
      $query = $this->database->delete('arch_order_access')
        ->condition('oid', $order->id());
      if ($realm) {
        $query->condition('realm', [$realm, 'all'], 'IN');
      }
      $query->execute();
    }
    // Only perform work when arch_order_access modules are active.
    if (
      !empty($grants)
      && count($this->moduleHandler->getImplementations('order_grants'))
    ) {
      $query = $this->database
        ->insert('arch_order_access')
        ->fields([
          'oid',
          'langcode',
          'fallback',
          'realm',
          'gid',
          'grant_view',
          'grant_update',
          'grant_delete',
        ]);

      foreach ($grants as $grant) {
        if ($realm && $realm != $grant['realm']) {
          continue;
        }
        if (isset($grant['langcode'])) {
          $grant_languages = [$grant['langcode'] => $this->languageManager->getLanguage($grant['langcode'])];
        }
        else {
          $grant_languages = $order->getTranslationLanguages(TRUE);
        }
        foreach ($grant_languages as $grant_langcode => $grant_language) {
          // Only write grants; denies are implicit.
          if ($grant['grant_view'] || $grant['grant_update'] || $grant['grant_delete']) {
            $grant['oid'] = $order->id();
            $grant['langcode'] = $grant_langcode;
            $grant['fallback'] = 0;
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
    $this->database->truncate('arch_order_access')->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function writeDefault() {
    $this->database->insert('arch_order_access')
      ->fields([
        'oid' => 0,
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
    return $this->database->query('SELECT COUNT(*) FROM {arch_order_access}')->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteOrderRecords(array $oids) {
    $this->database->delete('arch_order_access')
      ->condition('oid', $oids, 'IN')
      ->execute();
  }

  /**
   * Creates a query condition from an array of order access grants.
   *
   * @param array $order_access_grants
   *   An array of grants, as returned by order_access_grants().
   *
   * @return \Drupal\Core\Database\Query\Condition
   *   A condition object to be passed to $query->condition().
   *
   * @see order_access_grants()
   */
  protected static function buildGrantsQueryCondition(array $order_access_grants) {
    $grants = new Condition("OR");
    foreach ($order_access_grants as $realm => $gids) {
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
