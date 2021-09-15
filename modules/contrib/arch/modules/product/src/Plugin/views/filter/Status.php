<?php

namespace Drupal\arch_product\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter by published status.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("product_status")
 */
class Status extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {}

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $table = $this->ensureMyTable();
    $this->query->addWhereExpression(
      $this->options['group'],
      "{$table}.status = 1 OR ({$table}.uid = ***CURRENT_USER*** AND ***CURRENT_USER*** <> 0 AND ***VIEW_OWN_UNPUBLISHED_PRODUCTS*** = 1) OR ***BYPASS_PRODUCT_ACCESS*** = 1"
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    $contexts[] = 'user';

    return $contexts;
  }

}
