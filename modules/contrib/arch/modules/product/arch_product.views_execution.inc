<?php
/**
 * @file
 * Provide views runtime hooks for arch.module.
 */

use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Drupal\views\Analyzer;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_substitutions().
 */
function arch_product_views_query_substitutions(ViewExecutable $view) {
  $account = \Drupal::currentUser();
  return [
    '***ADMINISTER_PRODUCTS***' => intval($account->hasPermission('administer products')),
    '***VIEW_OWN_UNPUBLISHED_PRODUCTS***' => intval($account->hasPermission('view own unpublished products')),
    '***BYPASS_PRODUCT_ACCESS***' => intval($account->hasPermission('bypass product access')),
  ];
}

/**
 * Implements hook_views_analyze().
 */
function arch_product_views_analyze(ViewExecutable $view) {
  $ret = [];
  // Check for something other than the default display:
  if ($view->storage->get('base_table') == 'arch_product') {
    foreach ($view->displayHandlers as $display) {
      if (!$display->isDefaulted('access') || !$display->isDefaulted('filters')) {
        // Check for no access control.
        $access = $display->getOption('access');
        if (empty($access['type']) || $access['type'] == 'none') {
          $anonymous_role = Role::load(RoleInterface::ANONYMOUS_ID);
          $anonymous_has_access = $anonymous_role && $anonymous_role->hasPermission('access content');
          $authenticated_role = Role::load(RoleInterface::AUTHENTICATED_ID);
          $authenticated_has_access = $authenticated_role && $authenticated_role->hasPermission('access content');
          if (!$anonymous_has_access || !$authenticated_has_access) {
            $ret[] = Analyzer::formatMessage(t('Some roles lack permission to access content, but display %display has no access control.', ['%display' => $display->display['display_title']]), 'warning');
          }
          $filters = $display->getOption('filters');
          foreach ($filters as $filter) {
            if (
              $filter['table'] == 'arch_product'
              && (
                $filter['field'] == 'status'
                || $filter['field'] == 'status_extra'
              )
            ) {
              continue 2;
            }
          }
          $ret[] = Analyzer::formatMessage(t('Display %display has no access control but does not contain a filter for published products.', ['%display' => $display->display['display_title']]), 'warning');
        }
      }
    }
  }
  foreach ($view->displayHandlers as $display) {
    if ($display->getPluginId() == 'page') {
      if ($display->getOption('path') == 'product/%') {
        $ret[] = Analyzer::formatMessage(t('Display %display has set product/% as path. This will not produce what you want. If you want to have multiple versions of the product view, use panels.', ['%display' => $display->display['display_title']]), 'warning');
      }
    }
  }

  return $ret;
}
