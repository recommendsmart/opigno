<?php

namespace Drupal\arch_product\Plugin\views\row;

use Drupal\views\Plugin\views\row\EntityRow;

/**
 * Plugin which performs a product_view on the resulting object.
 *
 * Most of the code on this object is in the theme function.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "entity:product",
 * )
 */
class ProductRow extends EntityRow {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_mode']['default'] = 'teaser';

    return $options;
  }

}
