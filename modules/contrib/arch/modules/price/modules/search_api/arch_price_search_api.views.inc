<?php
/**
 * @file
 * Views hook implementations for the ArchPrice SearchAPI module.
 */

use Drupal\search_api\Entity\Index;

/**
 * Implements hook_views_data_alter().
 */
function arch_price_search_api_views_data_alter(array &$data) {
  /** @var \Drupal\search_api\IndexInterface $index */
  foreach (Index::loadMultiple() as $index) {
    try {
      // Fill in base data.
      $key = 'search_api_index_' . $index->id();
      $table = &$data[$key];
      $price_fields = [];
      if ($index->id() === 'products') {
        foreach ($table as $name => $field) {
          if (empty($field['field']['real field'])) {
            continue;
          }
          if (preg_match('%^entity:(.*?)/arch_price_(net|gross)(_currency)?_(.*?)%', $field['field']['real field'])) {
            $price_fields[] = $name;
          }
        }
      }

      if (!empty($price_fields)) {
        $table['arch_price_value_filter'] = [
          'filter' => [
            'id' => 'arch_price_search_api',
            'allow empty' => TRUE,
          ],
          'title' => 'Price filter',
          // @todo Add description.
          'help' => t('(No description available)'),
        ];
      }
    }
    catch (\Exception $e) {
      $args = [
        '%index' => $index->label(),
      ];
      watchdog_exception('search_api', $e, '%type while computing Views data for index %index: @message in %function (line %line of %file).', $args);
    }
  }
}
