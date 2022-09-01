<?php

/**
 * Implements hook_views_data().
 */
function opigno_calendar_views_data() {
  $data['views']['opigno_calendar_region'] = array(
    'title' => t('Opigno calendar region'),
    'help' => t('Opigno calendar region for add event link'),
    'area' => array(
      'id' => 'opigno_calendar_region',
    ),
  );

  $data['views']['opigno_calendar_today'] = array(
    'title' => t('Opigno calendar today'),
    'help' => t('Opigno calendar region for today button'),
    'area' => array(
      'id' => 'opigno_calendar_today',
    ),
  );
  return $data;
}
