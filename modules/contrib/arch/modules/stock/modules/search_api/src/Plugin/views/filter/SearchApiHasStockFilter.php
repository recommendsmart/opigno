<?php

namespace Drupal\arch_stock_search_api\Plugin\views\filter;

use Drupal\arch_stock\StockKeeperInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\Plugin\views\filter\SearchApiBoolean;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a stock filter to the view.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("arch_stock_has_stock_search_api")
 */
class SearchApiHasStockFilter extends SearchApiBoolean {

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Stock keeper.
   *
   * @var \Drupal\arch_stock\StockKeeperInterface
   */
  protected $stockKeeper;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountInterface $current_user,
    StockKeeperInterface $stock_keeper
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $this->currentUser = $current_user;
    $this->stockKeeper = $stock_keeper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('arch_stock.stock_keeper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $fields = $this->getFilteringFields();
      $group = $this->getQuery()->createConditionGroup('or', ['arch_stock']);
      foreach ($fields as $field) {
        $group->addCondition($field['property'], 0, '>');
      }
      $this->getQuery()->addConditionGroup($group);
    }
  }

  /**
   * Get filtering fields.
   *
   * @return string[][]
   *   List of filtering fields.
   */
  public function getFilteringFields() {
    $fields = [];
    foreach ($this->stockKeeper->selectWarehouses($this->currentUser) as $warehouse_id) {
      $field = strtolower('arch_stock_' . $warehouse_id);
      $fields[$field] = [
        'property' => $field,
      ];
    }
    return $fields;
  }

}
