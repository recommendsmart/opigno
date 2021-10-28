<?php

/**
 * @file
 * Post update functions for test module.
 */

/**
 * First update.
 */
function testing_post_post_update_first() {
  $execution = \Drupal::state()->get('post_update_test_execution', []);
  $execution[] = __FUNCTION__;
  \Drupal::state()->set('post_update_test_execution', $execution);

  return 'First update';
}

/**
 * 8001 update.
 */
function testing_post_post_update_8001() {
  $execution = \Drupal::state()->get('post_update_test_execution', []);
  $execution[] = __FUNCTION__;
  \Drupal::state()->set('post_update_test_execution', $execution);

  return '8001 update';
}

/**
 * Implements hook_removed_post_updates().
 */
function testing_post_removed_post_updates() {
  return [
    'testing_post_post_update_foo' => '8.x-1.0',
    'testing_post_post_update_bar' => '8.x-2.0',
    'testing_post_post_update_pub' => '3.0.0',
    'testing_post_post_update_baz' => '3.0.0',
  ];
}
