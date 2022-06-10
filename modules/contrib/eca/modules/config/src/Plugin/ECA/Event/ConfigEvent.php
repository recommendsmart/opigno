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
   * {@inheritdoc}
   */
  public static function definitions(): array {
    $actions = [
      'delete' => [
        'label' => 'Delete config',
        'event_name' => ConfigEvents::DELETE,
        'event_class' => ConfigCrudEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'collection_info' => [
        'label' => 'Collect information on all config collections',
        'event_name' => ConfigEvents::COLLECTION_INFO,
        'event_class' => ConfigCollectionInfo::class,
        'tags' => Tag::READ | Tag::PERSISTENT | Tag::AFTER,
      ],
      'import' => [
        'label' => 'Import config',
        'event_name' => ConfigEvents::IMPORT,
        'event_class' => ConfigImporterEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'import_missing_content' => [
        'label' => 'Import config but content missing',
        'event_name' => ConfigEvents::IMPORT_MISSING_CONTENT,
        'event_class' => MissingContentEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
      'import_validate' => [
        'label' => 'Import config validation',
        'event_name' => ConfigEvents::IMPORT_VALIDATE,
        'event_class' => ConfigImporterEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
      'rename' => [
        'label' => 'Rename config',
        'event_name' => ConfigEvents::RENAME,
        'event_class' => ConfigRenameEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
      'save' => [
        'label' => 'Save config',
        'event_name' => ConfigEvents::SAVE,
        'event_class' => ConfigCrudEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'storage_transform_export' => [
        'label' => 'Start config export',
        'event_name' => ConfigEvents::STORAGE_TRANSFORM_EXPORT,
        'event_class' => StorageTransformEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
      'storage_transform_import' => [
        'label' => 'Start config import',
        'event_name' => ConfigEvents::STORAGE_TRANSFORM_IMPORT,
        'event_class' => StorageTransformEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
    ];
    if (class_exists(ConfigTranslationEvents::class)) {
      $actions['populate_mapper'] = [
        'label' => 'Config manager populated',
        'event_name' => ConfigTranslationEvents::POPULATE_MAPPER,
        'event_class' => ConfigMapperPopulateEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::RUNTIME | Tag::AFTER,
      ];
    }
    if (class_exists(LanguageConfigOverrideEvents::class)) {
      $actions['delete_override'] = [
        'label' => 'Delete config override',
        'event_name' => LanguageConfigOverrideEvents::DELETE_OVERRIDE,
        'event_class' => LanguageConfigOverrideCrudEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ];
      $actions['save_override'] = [
        'label' => 'Save config override',
        'event_name' => LanguageConfigOverrideEvents::SAVE_OVERRIDE,
        'event_class' => LanguageConfigOverrideCrudEvent::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ];
    }
    return $actions;
  }

}
