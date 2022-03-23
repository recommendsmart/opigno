<?php

namespace Drupal\eca_config\Plugin\ECA\Event;

use Drupal\config_translation\Event\ConfigMapperPopulateEvent;
use Drupal\config_translation\Event\ConfigTranslationEvents;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\language\Config\LanguageConfigOverrideCrudEvent;
use Drupal\language\Config\LanguageConfigOverrideEvents;
use Drupal\Core\Config\ConfigCollectionInfo;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\Core\Config\Importer\MissingContentEvent;
use Drupal\Core\Config\StorageTransformEvent;
use Drupal\eca\Event\Tag;

/**
 * Plugin implementation of the ECA Events for config.
 *
 * @EcaEvent(
 *   id = "config",
 *   deriver = "Drupal\eca_config\Plugin\ECA\Event\ConfigEventDeriver"
 * )
 */
class ConfigEvent extends EventBase {

  /**
   * @return array[]
   */
  public static function actions(): array {
    $actions = [
      'delete' => [
        'label' => 'Delete config',
        'drupal_id' => ConfigEvents::DELETE,
        'drupal_event_class' => ConfigCrudEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'collection_info' => [
        'label' => 'Collect information on all config collections',
        'drupal_id' => ConfigEvents::COLLECTION_INFO,
        'drupal_event_class' => ConfigCollectionInfo::class,
        'tags' => Tag::READ | Tag::PERSISTENT | Tag::AFTER,
      ],
      'import' => [
        'label' => 'Import config',
        'drupal_id' => ConfigEvents::IMPORT,
        'drupal_event_class' => ConfigImporterEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'import_missing_content' => [
        'label' => 'Import config but content missing',
        'drupal_id' => ConfigEvents::IMPORT_MISSING_CONTENT,
        'drupal_event_class' => MissingContentEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
      'import_validate' => [
        'label' => 'Import config validation',
        'drupal_id' => ConfigEvents::IMPORT_VALIDATE,
        'drupal_event_class' => ConfigImporterEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
      'rename' => [
        'label' => 'Rename config',
        'drupal_id' => ConfigEvents::RENAME,
        'drupal_event_class' => ConfigRenameEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
      'save' => [
        'label' => 'Save config',
        'drupal_id' => ConfigEvents::SAVE,
        'drupal_event_class' => ConfigCrudEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'storage_transform_export' => [
        'label' => 'Start config export',
        'drupal_id' => ConfigEvents::STORAGE_TRANSFORM_EXPORT,
        'drupal_event_class' => StorageTransformEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
      'storage_transform_import' => [
        'label' => 'Start config import',
        'drupal_id' => ConfigEvents::STORAGE_TRANSFORM_IMPORT,
        'drupal_event_class' => StorageTransformEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
    ];
    if (class_exists(ConfigTranslationEvents::class)) {
      $actions['populate_mapper'] = [
        'label' => 'Config manager populated',
        'drupal_id' => ConfigTranslationEvents::POPULATE_MAPPER,
        'drupal_event_class' => ConfigMapperPopulateEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::RUNTIME | Tag::AFTER,
      ];
    }
    if (class_exists(LanguageConfigOverrideEvents::class)) {
      $actions['delete_override'] = [
        'label' => 'Delete config override',
        'drupal_id' => LanguageConfigOverrideEvents::DELETE_OVERRIDE,
        'drupal_event_class' => LanguageConfigOverrideCrudEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ];
      $actions['save_override'] = [
        'label' => 'Save config override',
        'drupal_id' => LanguageConfigOverrideEvents::SAVE_OVERRIDE,
        'drupal_event_class' => LanguageConfigOverrideCrudEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ];
    }
    return $actions;
  }

}
