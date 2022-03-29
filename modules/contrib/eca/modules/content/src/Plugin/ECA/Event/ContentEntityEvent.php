<?php

namespace Drupal\eca_content\Plugin\ECA\Event;

use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Event\Tag;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\eca_content\EntityTypeTrait;
use Drupal\eca_content\Event\ContentEntityBundleCreate;
use Drupal\eca_content\Event\ContentEntityBundleDelete;
use Drupal\eca_content\Event\ContentEntityCreate;
use Drupal\eca_content\Event\ContentEntityCustomEvent;
use Drupal\eca_content\Event\ContentEntityDelete;
use Drupal\eca_content\Event\ContentEntityEvents;
use Drupal\eca_content\Event\ContentEntityFieldValuesInit;
use Drupal\eca_content\Event\ContentEntityInsert;
use Drupal\eca_content\Event\ContentEntityLoad;
use Drupal\eca_content\Event\ContentEntityPreDelete;
use Drupal\eca_content\Event\ContentEntityPreLoad;
use Drupal\eca_content\Event\ContentEntityPrepareForm;
use Drupal\eca_content\Event\ContentEntityPrepareView;
use Drupal\eca_content\Event\ContentEntityPreSave;
use Drupal\eca_content\Event\ContentEntityRevisionCreate;
use Drupal\eca_content\Event\ContentEntityRevisionDelete;
use Drupal\eca_content\Event\ContentEntityStorageLoad;
use Drupal\eca_content\Event\ContentEntityTranslationCreate;
use Drupal\eca_content\Event\ContentEntityTranslationDelete;
use Drupal\eca_content\Event\ContentEntityTranslationInsert;
use Drupal\eca_content\Event\ContentEntityUpdate;
use Drupal\eca_content\Event\ContentEntityView;

/**
 * Plugin implementation of the ECA Events for content entities.
 *
 * @EcaEvent(
 *   id = "content_entity",
 *   deriver = "Drupal\eca_content\Plugin\ECA\Event\ContentEntityEventDeriver"
 * )
 */
class ContentEntityEvent extends EventBase {

  use EntityTypeTrait;

  /**
   * @return array[]
   */
  public static function actions(): array {
    return [
      'bundlecreate' => [
        'label' => 'Create content entity bundle',
        'event_name' => ContentEntityEvents::BUNDLECREATE,
        'event_class' => ContentEntityBundleCreate::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'bundledelete' => [
        'label' => 'Delete content entity bundle',
        'event_name' => ContentEntityEvents::BUNDLEDELETE,
        'event_class' => ContentEntityBundleDelete::class,
        'tags' => Tag::CONFIG | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'create' => [
        'label' => 'Create content entity',
        'event_name' => ContentEntityEvents::CREATE,
        'event_class' => ContentEntityCreate::class,
        'tags' => Tag::CONTENT | Tag::WRITE | Tag::RUNTIME | Tag::AFTER,
      ],
      'revisioncreate' => [
        'label' => 'Create content entity revision',
        'event_name' => ContentEntityEvents::REVISIONCREATE,
        'event_class' => ContentEntityRevisionCreate::class,
        'tags' => Tag::CONTENT | Tag::WRITE | Tag::RUNTIME | Tag::AFTER,
      ],
      'preload' => [
        'label' => 'Preload content entity',
        'event_name' => ContentEntityEvents::PRELOAD,
        'event_class' => ContentEntityPreLoad::class,
        'tags' => Tag::READ | Tag::BEFORE,
      ],
      'load' => [
        'label' => 'Load content entity',
        'event_name' => ContentEntityEvents::LOAD,
        'event_class' => ContentEntityLoad::class,
        'tags' => Tag::CONTENT | Tag::READ | Tag::AFTER,
      ],
      'storageload' => [
        'label' => 'Load content entity from storage',
        'event_name' => ContentEntityEvents::STORAGELOAD,
        'event_class' => ContentEntityStorageLoad::class,
        'tags' => Tag::CONTENT | Tag::READ | Tag::PERSISTENT | Tag::AFTER,
      ],
      'presave' => [
        'label' => 'Presave content entity',
        'event_name' => ContentEntityEvents::PRESAVE,
        'event_class' => ContentEntityPreSave::class,
        'tags' => Tag::CONTENT | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
      'insert' => [
        'label' => 'Insert content entity',
        'event_name' => ContentEntityEvents::INSERT,
        'event_class' => ContentEntityInsert::class,
        'tags' => Tag::CONTENT | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'update' => [
        'label' => 'Update content entity',
        'event_name' => ContentEntityEvents::UPDATE,
        'event_class' => ContentEntityUpdate::class,
        'tags' => Tag::CONTENT | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'translationcreate' => [
        'label' => 'Create content entity translation',
        'event_name' => ContentEntityEvents::TRANSLATIONCREATE,
        'event_class' => ContentEntityTranslationCreate::class,
        'tags' => Tag::CONTENT | Tag::WRITE | Tag::RUNTIME | Tag::AFTER,
      ],
      'translationinsert' => [
        'label' => 'Insert content entity translation',
        'event_name' => ContentEntityEvents::TRANSLATIONINSERT,
        'event_class' => ContentEntityTranslationInsert::class,
        'tags' => Tag::CONTENT | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'translationdelete' => [
        'label' => 'Delete content entity translation',
        'event_name' => ContentEntityEvents::TRANSLATIONDELETE,
        'event_class' => ContentEntityTranslationDelete::class,
        'tags' => Tag::CONTENT | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'predelete' => [
        'label' => 'Predelete content entity',
        'event_name' => ContentEntityEvents::PREDELETE,
        'event_class' => ContentEntityPreDelete::class,
        'tags' => Tag::CONTENT | Tag::WRITE | Tag::PERSISTENT | Tag::BEFORE,
      ],
      'delete' => [
        'label' => 'Delete content entity',
        'event_name' => ContentEntityEvents::DELETE,
        'event_class' => ContentEntityDelete::class,
        'tags' => Tag::CONTENT | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'revisiondelete' => [
        'label' => 'Delete content entity revision',
        'event_name' => ContentEntityEvents::REVISIONDELETE,
        'event_class' => ContentEntityRevisionDelete::class,
        'tags' => Tag::CONTENT | Tag::WRITE | Tag::PERSISTENT | Tag::AFTER,
      ],
      'view' => [
        'label' => 'View content entity',
        'event_name' => ContentEntityEvents::VIEW,
        'event_class' => ContentEntityView::class,
        'tags' => Tag::CONTENT | Tag::RUNTIME | Tag::VIEW | Tag::BEFORE,
      ],
      'prepareview' => [
        'label' => 'Prepare content entity view',
        'event_name' => ContentEntityEvents::PREPAREVIEW,
        'event_class' => ContentEntityPrepareView::class,
        'tags' => Tag::CONTENT | Tag::RUNTIME | Tag::VIEW | Tag::BEFORE,
      ],
      'prepareform' => [
        'label' => 'Prepare content entity form',
        'event_name' => ContentEntityEvents::PREPAREFORM,
        'event_class' => ContentEntityPrepareForm::class,
        'tags' => Tag::CONTENT | Tag::RUNTIME | Tag::VIEW | Tag::BEFORE,
      ],
      'fieldvaluesinit' => [
        'label' => 'Init content entity field values',
        'event_name' => ContentEntityEvents::FIELDVALUESINIT,
        'event_class' => ContentEntityFieldValuesInit::class,
        'tags' => Tag::CONTENT | Tag::WRITE | Tag::RUNTIME | Tag::AFTER,
      ],
      'custom' => [
        'label' => 'ECA content entity custom event',
        'event_name' => ContentEntityEvents::CUSTOM,
        'event_class' => ContentEntityCustomEvent::class,
        'tags' => Tag::CONTENT | Tag::WRITE | Tag::RUNTIME | Tag::AFTER,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    if ($this->eventClass() === ContentEntityCustomEvent::class) {
      return ContentEntityCustomEvent::fields();
    }
    $fields = parent::fields();
    $fields[] = $this->bundleField(TRUE);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function lazyLoadingWildcard(string $eca_config_id, EcaEvent $ecaEvent): string {
    /** @var \Drupal\eca\Plugin\ECA\Event\EventBase $plugin */
    $plugin = $ecaEvent->getPlugin();
    switch ($plugin->getDerivativeId()) {

      case 'custom':
        $configuration = $ecaEvent->getConfiguration();
        return isset($configuration['event_id']) ? trim($configuration['event_id']) : '';

      case 'preload':
        $type = $ecaEvent->getConfiguration()['type'] ?? '_all';
        if ($type === '_all') {
          return '*';
        }
        [$entityType] = explode(' ', $type);
        return $entityType;

      default:
        $type = $ecaEvent->getConfiguration()['type'] ?? '_all';
        if ($type === '_all') {
          return '*';
        }
        [$entityType, $bundle] = array_merge(explode(' ', $type), ['_all']);
        if ($bundle === '_all') {
          return $entityType;
        }
        return $entityType . '::' . $bundle;

    }
  }

}
