<?php

namespace Drupal\opigno_social;

use Drupal\views\EntityViewsData;

/**
 * Opigno post views data handler.
 *
 * @package Drupal\opigno_social
 */
class OpignoPostViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // View filter for the user social posts.
    $data['opigno_post']['opigno_available_posts'] = [
      'help' => $this->t("Get the list of posts available for the current user."),
      'real field' => 'uid',
      'filter' => [
        'title' => $this->t("Opigno available posts"),
        'id' => 'opigno_available_posts',
      ],
    ];

    // View sorting: pinned first, then newest.
    $data['opigno_post']['pinned_first'] = [
      'title' => $this->t('Opigno Pinned posts first'),
      'group' => $this->t('Opigno post/comment'),
      'help' => $this->t('Display pinned posts first, then others.'),
      'sort' => [
        'field' => 'created',
        'id' => 'pinned_first',
      ],
    ];

    return $data;
  }

}
