<?php

namespace Drupal\eca_migrate\Plugin\ECA\Event;

use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateIdMapMessageEvent;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigrateMapDeleteEvent;
use Drupal\migrate\Event\MigrateMapSaveEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Event\MigrateRollbackEvent;
use Drupal\migrate\Event\MigrateRowDeleteEvent;

/**
 * Plugin implementation of the ECA Events for migrate.
 *
 * @EcaEvent(
 *   id = "migrate",
 *   deriver = "Drupal\eca_migrate\Plugin\ECA\Event\MigrateEventDeriver"
 * )
 */
class MigrateEvent extends EventBase {

  /**
   * @return array[]
   */
  public static function actions(): array {
    $actions = [];
    $actions['idmap_message'] = [
      'label' => 'Save message to ID map',
      'drupal_id' => MigrateEvents::IDMAP_MESSAGE,
      'drupal_event_class' => MigrateIdMapMessageEvent::class,
    ];
    $actions['map_delete'] = [
      'label' => 'Remove entry from migration map',
      'drupal_id' => MigrateEvents::MAP_DELETE,
      'drupal_event_class' => MigrateMapDeleteEvent::class,
    ];
    $actions['map_save'] = [
      'label' => 'Save to migration map',
      'drupal_id' => MigrateEvents::MAP_SAVE,
      'drupal_event_class' => MigrateMapSaveEvent::class,
    ];
    $actions['post_import'] = [
      'label' => 'Migration import finished',
      'drupal_id' => MigrateEvents::POST_IMPORT,
      'drupal_event_class' => MigrateImportEvent::class,
    ];
    $actions['post_rollback'] = [
      'label' => 'Migration rollback finished',
      'drupal_id' => MigrateEvents::POST_ROLLBACK,
      'drupal_event_class' => MigrateRollbackEvent::class,
    ];
    $actions['post_row_delete'] = [
      'label' => 'Migration row deleted',
      'drupal_id' => MigrateEvents::POST_ROW_DELETE,
      'drupal_event_class' => MigrateRowDeleteEvent::class,
    ];
    $actions['post_row_save'] = [
      'label' => 'Migration row saved',
      'drupal_id' => MigrateEvents::POST_ROW_SAVE,
      'drupal_event_class' => MigratePostRowSaveEvent::class,
    ];
    $actions['pre_import'] = [
      'label' => 'Migration import started',
      'drupal_id' => MigrateEvents::PRE_IMPORT,
      'drupal_event_class' => MigrateImportEvent::class,
    ];
    $actions['pre_rollback'] = [
      'label' => 'Migration rollback started',
      'drupal_id' => MigrateEvents::PRE_ROLLBACK,
      'drupal_event_class' => MigrateRollbackEvent::class,
    ];
    $actions['pre_row_delete'] = [
      'label' => 'Deleting migration row',
      'drupal_id' => MigrateEvents::PRE_ROW_DELETE,
      'drupal_event_class' => MigrateRowDeleteEvent::class,
    ];
    $actions['pre_row_save'] = [
      'label' => 'Saving migration row',
      'drupal_id' => MigrateEvents::PRE_ROW_SAVE,
      'drupal_event_class' => MigratePreRowSaveEvent::class,
    ];
    return $actions;
  }

}
