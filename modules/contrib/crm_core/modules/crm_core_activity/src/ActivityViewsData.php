<?php

namespace Drupal\crm_core_activity;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the activity entity type.
 */
class ActivityViewsData extends EntityViewsData {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['crm_core_activity']['activity_preview'] = [
      'title' => $this->t('Activity preview field'),
      'field' => [
        'title' => $this->t('Activity preview'),
        'help' => $this->t('Provide preview of activity'),
        'id' => 'activity_preview',
        'field' => 'activity_id',
      ],
    ];

    return $data;
  }

}
