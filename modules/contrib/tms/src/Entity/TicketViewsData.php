<?php

namespace Drupal\tms\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Ticket entities.
 */
class TicketViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data_table = $this->entityType->getDataTable();
    $fields = &$data['ticket'];
    foreach($fields as &$field) {
      if(isset($field['filter']) && isset($field['relationship']) && $field['relationship']['base'] === 'taxonomy_term_field_data') {
        $field['filter']['id'] = 'taxonomy_index_tid';
       
      }      
    }
    return $data;
  }

}
