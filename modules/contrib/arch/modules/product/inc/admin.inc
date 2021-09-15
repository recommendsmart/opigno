<?php
/**
 * @file
 * Content administration and module settings user interface.
 */

use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Link;

/**
 * Updates all products in the passed-in array with the passed-in field values.
 *
 * IMPORTANT NOTE: This function is intended to work when called from a form
 * submission handler. Calling it outside of the form submission process may not
 * work correctly.
 *
 * @param array $products
 *   Array of product pids or products to update.
 * @param array $updates
 *   Array of key/value pairs with product field names and the value to update
 *   that field to.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 * @param bool $load
 *   (optional) TRUE if $products contains an array of product IDs to be loaded,
 *   FALSE if it contains fully loaded products. Defaults to FALSE.
 * @param bool $revisions
 *   (optional) TRUE if $products contains an array of revision IDs instead of
 *   product IDs. Defaults to FALSE; will be ignored if $load is FALSE.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function product_mass_update(array $products, array $updates, $langcode = NULL, $load = FALSE, $revisions = FALSE) {
  // We use batch processing to prevent timeout when updating a large number
  // of products.
  if (count($products) > 10) {
    $batch = [
      'operations' => [
        [
          '_product_mass_update_batch_process',
          [
            $products,
            $updates,
            $langcode,
            $load,
            $revisions,
          ],
        ],
      ],
      'finished' => '_product_mass_update_batch_finished',
      'title' => t('Processing'),
      // We use a single multi-pass operation, so the default
      // 'Remaining x of y operations' message will be confusing here.
      'progress_message' => '',
      'error_message' => t('The update has encountered an error.'),
      // The operations do not live in the .module file, so we need to
      // tell the batch engine which file to load before calling them.
      'file' => drupal_get_path('module', 'arch') . '/inc/admin.inc',
    ];
    batch_set($batch);
  }
  else {
    $storage = \Drupal::entityTypeManager()->getStorage('product');
    if ($load && !$revisions) {
      $products = $storage->loadMultiple($products);
    }
    foreach ($products as $product) {
      if ($load && $revisions) {
        $product = $storage->loadRevision($product);
      }
      _product_mass_update_helper($product, $updates, $langcode);
    }
    \Drupal::service('messenger')->addMessage(
      t('The update has been performed.'),
      'status'
    );
  }
}

/**
 * Updates individual products when fewer than 10 are queued.
 *
 * @param Drupal\arch_product\Entity\ProductInterface $product
 *   A product to update.
 * @param array $updates
 *   Associative array of updates.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 *
 * @return \Drupal\arch_product\Entity\ProductInterface
 *   An updated product object.
 *
 * @see product_mass_update()
 */
function _product_mass_update_helper(ProductInterface $product, array $updates, $langcode = NULL) {
  $langcodes = isset($langcode) ? [$langcode] : array_keys($product->getTranslationLanguages());
  // For efficiency manually save the original product before applying any
  // changes.
  $product->original = clone $product;
  foreach ($langcodes as $langcode) {
    foreach ($updates as $name => $value) {
      $product->getTranslation($langcode)->$name = $value;
    }
  }
  $product->save();
  return $product;
}

/**
 * Implements callback_batch_operation().
 *
 * Executes a batch operation for product_mass_update().
 *
 * @param array $products
 *   An array of product IDs.
 * @param array $updates
 *   Associative array of updates.
 * @param string $langcode
 *   The language updates should be applied to. If none is specified all
 *   available languages are processed.
 * @param bool $load
 *   TRUE if $products contains an array of product IDs to be loaded, FALSE if
 *   it contains fully loaded products.
 * @param bool $revisions
 *   (optional) TRUE if $products contains an array of revision IDs instead of
 *   product IDs. Defaults to FALSE; will be ignored if $load is FALSE.
 * @param array|\ArrayAccess $context
 *   An array of contextual key/values.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function _product_mass_update_batch_process(array $products, array $updates, $langcode, $load, $revisions, &$context) {
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($products);
    $context['sandbox']['products'] = $products;
  }

  // Process products by groups of 5.
  $storage = \Drupal::entityTypeManager()->getStorage('product');
  $count = min(5, count($context['sandbox']['products']));
  for ($i = 1; $i <= $count; $i++) {
    // For each pid, load the product, reset the values, and save it.
    $product = array_shift($context['sandbox']['products']);
    if ($load) {
      $product = $revisions ?
        $storage->loadRevision($product) : $storage->load($product);
    }
    $product = _product_mass_update_helper($product, $updates, $langcode);

    // Store result for post-processing in the finished callback.
    $context['results'][] = Link::fromTextAndUrl($product->label(), $product->toUrl());

    // Update our progress information.
    $context['sandbox']['progress']++;
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Implements callback_batch_finished().
 *
 * Reports the 'finished' status of batch operation for product_mass_update().
 *
 * @param bool $success
 *   A boolean indicating whether the batch mass update operation successfully
 *   concluded.
 * @param string[] $results
 *   An array of rendered links to products updated via the batch mode process.
 * @param array $operations
 *   An array of function calls (not used in this function).
 *
 * @see _product_mass_update_batch_process()
 */
function _product_mass_update_batch_finished($success, array $results, array $operations) {
  if ($success) {
    \Drupal::service('messenger')->addMessage(
      t('The update has been performed.'),
      'status'
    );
  }
  else {
    \Drupal::service('messenger')->addMessage(
      t('An error occurred and processing did not complete.'),
      'error'
    );
    $message = \Drupal::translation()->formatPlural(count($results), '1 item successfully processed:', '@count items successfully processed:');
    $item_list = [
      '#theme' => 'item_list',
      '#items' => $results,
    ];
    $message .= \Drupal::service('renderer')->render($item_list);
    \Drupal::service('messenger')->addMessage(
      $message,
      'status'
    );
  }
}
