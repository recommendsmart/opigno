<?php

/**
 * @file
 * Provide views data for our Add Content by Bundle module.
 */

/**
 * Implements hook_views_data().
 */
function display_link_plus_views_data() {

  $data['views']['display_link_plus'] = [
    'title' => t('Display Link Plus'),
    'help' => t('Provide a configurable link to another display in this view.'),
    'area' => [
      'id' => 'display_link_plus',
    ],
  ];

  return $data;
}
