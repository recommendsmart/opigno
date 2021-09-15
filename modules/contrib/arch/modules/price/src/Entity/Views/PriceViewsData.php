<?php

namespace Drupal\arch_price\Entity\Views;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the price entity type.
 */
class PriceViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['price_field_data']['table']['base']['help'] = $this->t('Prices are attached to product.', [], ['context' => 'arch_price']);
    $data['price_field_data']['table']['base']['access query tag'] = 'price_access';
    $data['price_field_data']['table']['wizard_id'] = 'price';

    $data['price_field_data']['price_type']['help'] = $this->t('Filter the results of "Price" to a particular type.', [], ['context' => 'arch_price']);
    $data['price_field_data']['price_type']['field']['help'] = $this->t('The price type name.', [], ['context' => 'arch_price']);
    $data['price_field_data']['price_type']['argument']['id'] = 'price_type';
    unset($data['price_field_data']['price_type']['sort']);

    $data['price_field_data']['description__value']['field']['click sortable'] = FALSE;

    return $data;
  }

}
