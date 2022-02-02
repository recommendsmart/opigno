<?php

/**
 * @file
 * Post update functions for Type Tray.
 */

/**
 * Enable Existing Nodes Link on existing installs.
 */
function type_tray_post_update_enable_existing_node_links(&$sandbox) {
  \Drupal::configFactory()
    ->getEditable('type_tray.settings')
    ->set('existing_nodes_link', TRUE)
    ->save(TRUE);
}
