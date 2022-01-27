<?php

namespace Drupal\digital_signage_framework\Entity;

use Drupal;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\digital_signage_framework\DigitalSignageFrameworkEvents;
use Drupal\digital_signage_framework\DeviceInterface;
use Drupal\digital_signage_framework\Controller\Api;
use Drupal\digital_signage_framework\Event\Libraries;
use Drupal\digital_signage_framework\PlatformInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Defines the device entity class.
 *
 * @ContentEntityType(
 *   id = "digital_signage_device",
 *   label = @Translation("Digital signage device"),
 *   label_collection = @Translation("Digital signage devices"),
 *   bundle_label = @Translation("Device type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\digital_signage_framework\DeviceListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\digital_signage_framework\DeviceAccessControlHandler",
 *     "form" = {
 *       "edit" = "Drupal\digital_signage_framework\Form\Device",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "digital_signage_device",
 *   data_table = "digital_signage_device_field_data",
 *   revision_table = "digital_signage_device_revision",
 *   revision_data_table = "digital_signage_device_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer digital signage device types",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "langcode" = "langcode",
 *     "bundle" = "bundle",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "canonical" = "/admin/content/digital_signage_device/{digital_signage_device}",
 *     "edit-form" =
 *   "/admin/content/digital-signage-device/{digital_signage_device}/edit",
 *     "collection" = "/admin/content/digital-signage-device"
 *   },
 *   bundle_entity_type = "digital_signage_device_type",
 *   field_ui_base_route = "entity.digital_signage_device_type.edit_form"
 * )
 */
class Device extends RevisionableContentEntityBase implements DeviceInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    if (isset($this->original)) {
      $originalSetting = $this->original->getEmergencyEntity();
      $currentSetting = $this->getEmergencyEntity();
      if ($originalSetting === NULL && $currentSetting === NULL) {
        // No current or past emergency mode.
        return;
      }
      else if ($originalSetting === NULL) {
        // Turn on emergency mode.
        $this->getPlugin()->setEmergencyMode($this, $currentSetting->getReverseEntityType(), $currentSetting->getReverseEntityId());
      }
      else if ($currentSetting === NULL) {
        // Turn off emergency mode.
        $this->getPlugin()->disableEmergencyMode($this);
      }
      else {
        $originalEntity = $originalSetting->getReverseEntity();
        $currentEntity = $currentSetting->getReverseEntity();
        if ($originalEntity['target_id'] !== $currentEntity['target_id'] || $originalEntity['target_type'] !== $currentEntity['target_type']) {
          // Change emergency mode.
          $this->getPlugin()->setEmergencyMode($this, $currentSetting->getReverseEntityType(), $currentSetting->getReverseEntityId());
        }
      }
    }
  }

  private function getTempScheduleStoreId() {
    return 'temp-schedule-store-' . $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin(): PlatformInterface {
    return Drupal::service('plugin.manager.digital_signage_platform')->createInstance($this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getApiSpec($debug = FALSE, $reload_assets = FALSE, $reload_content = FALSE): array {
    $event = new Libraries($this);
    $event->addLibrary('digital_signage_framework/schedule.content');
    $event->addLibrary('digital_signage_framework/schedule.timer');
    Drupal::service('event_dispatcher')->dispatch(DigitalSignageFrameworkEvents::LIBRARIES, $event);
    // The following code is copied over from Drupal core.
    // @see \Drupal\Core\Ajax\AjaxResponseAttachmentsProcessor::buildAttachmentsCommands
    $assets = new AttachedAssets();
    $assets->setLibraries($event->getLibraries());
    $assets->setSettings($event->getSettings());
    /** @var AssetResolverInterface $assetResolver */
    $assetResolver = Drupal::service('asset.resolver');
    [$js_assets_header, $js_assets_footer] = $assetResolver->getJsAssets($assets, FALSE);
    $drupalSettings = [];
    if (isset($js_assets_header['drupalSettings'])) {
      $drupalSettings = $js_assets_header['drupalSettings']['data'];
      unset($js_assets_header['drupalSettings']);
    }
    if (isset($js_assets_footer['drupalSettings'])) {
      $drupalSettings = $js_assets_footer['drupalSettings']['data'];
      unset($js_assets_footer['drupalSettings']);
    }
    $scripts = [];
    $scriptIndex = 0;
    foreach ([$js_assets_header, $js_assets_footer] as $js_assets) {
      foreach ($js_assets as $name => $js_asset) {
        if ($js_asset['type'] === 'file' && $name !== 'core/misc/drupalSettingsLoader.js') {
          $scriptIndex++;
          $scripts[] = [
            'uri' => file_create_url($js_asset['data']),
            'uid' => 'ajs-' . $scriptIndex . '.js',
          ];
        }
      }
    }
    $config = Drupal::service('config.factory')->get('digital_signage_framework.settings');
    $spec = [
      'api' => Url::fromRoute('digital_signage_framework.api', [], [
        'absolute' => TRUE,
      ])->toString(),
      'baseUrl' => trim(Url::fromUserInput('/', [
        'absolute' => TRUE,
        'language' => $this->languageManager()->getLanguage(LanguageInterface::LANGCODE_NOT_SPECIFIED),
      ])->toString(), '/'),
      'deviceId' => $this->id(),
      'httpHeader' => Yaml::decode($config->get('http_header')) ?? [],
      'fonts' => [],
      'emergencyEntity' => [],
      'debug' => $debug,
      'reloadassets' => $reload_assets,
      'reloadcontent' => $reload_content,
      'drupalSettings' => $drupalSettings,
      'scripts' => $scripts,
      'refreshInterval' => $config->get('schedule.dynamic_content.refresh'),
    ];
    $spec['httpHeader']['x-digsig-fingerprint'] = Api::fingerprint($this);
    $fonts = Yaml::decode($config->get('fonts')) ?? [];
    foreach ($fonts as $font) {
      if ($font['enabled']) {
        foreach ($font['formats'] as $format => $url) {
          if (strpos($url, 'http') !== 0) {
            $font['formats'][$format] = $spec['baseUrl'] . $url;
          }
        }
        $spec['fonts'][] = [
          'uid' => implode('-', [
            $font['family'],
            $font['weight'],
            $font['style'],
            $font['stretch']
          ]),
          'fontFamily' => $font['family'],
          'fontWeight' => $font['weight'],
          'fontStyle' => $font['style'],
          'fontStretch' => $font['stretch'],
          'unicodeRange' => $font['urange'],
          'formats' => $font['formats'],
        ];
      }
    }
    if (($emergencyEntity = $this->getEmergencyEntity()) && ($reverse_entity = $emergencyEntity->getReverseEntity())) {
      $spec['emergencyEntity'] = [
        'id' => (int) $reverse_entity['target_id'],
        'type' => $reverse_entity['target_type'],
      ];
    }
    return $spec;
  }

  /**
   * {@inheritdoc}
   */
  public function extId(): string {
    return $this->get('extid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title): DeviceInterface {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status): DeviceInterface {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function needsScheduleUpdate(): bool {
    return (bool) $this->get('needs_update')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function scheduleUpdate(): DeviceInterface {
    $this->getPlugin()->deleteRecord($this->getTempScheduleStoreId());
    if (!$this->needsScheduleUpdate()) {
      $this->set('needs_update', TRUE);
      $this->save();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function scheduleUpdateCompleted(): DeviceInterface {
    $this->set('needs_update', FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function addSegment($segment): bool {
    /** @var Term[] $segmentEntities */
    $segmentEntities = taxonomy_term_load_multiple_by_name($segment, 'digital_signage');
    if (empty($segmentEntities)) {
      $term = Term::create([
        'vid' => 'digital_signage',
        'name' => $segment,
      ]);
      $term->save();
      $segmentEntities = [$term];
    }

    $changed = FALSE;
    /** @var Term[] $existingSegments */
    $existingSegments = $this->get('segments')->referencedEntities();
    $existingSegmentIds = $this->getSegmentIds();
    foreach ($segmentEntities as $segmentEntity) {
      if (!in_array($segmentEntity->id(), $existingSegmentIds, TRUE)) {
        $existingSegments[] = $segmentEntity;
        $changed = TRUE;
      }
    }
    if ($changed) {
      $this->set('segments', $existingSegments);
    }
    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function getSegmentIds(): array {
    $ids = [];
    /** @var \Drupal\taxonomy\TermInterface $entity */
    foreach ($this->get('segments')->referencedEntities() as $entity) {
      $ids[] = $entity->id();
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchedule($stored = TRUE) {
    if ($stored) {
      return $this->get('schedule')->referencedEntities()[0] ?? NULL;
    }
    $plugin = $this->getPlugin();
    $id = $this->getTempScheduleStoreId();
    $schedule = $plugin->getRecord($id);
    if ($schedule === NULL) {
      if ($schedule = Drupal::service('schedule.manager.digital_signage_platform')->getSchedule($this)) {
        $plugin->storeRecord($id, $schedule);
      }
    }
    return $schedule;
  }

  /**
   * {@inheritdoc}
   */
  public function setSchedule($schedule): DeviceInterface {
    $this->set('schedule', $schedule);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidth(): int {
    return $this->get('size')->getValue()[0]['width'] ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeight(): int {
    return $this->get('size')->getValue()[0]['height'] ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrientation(): string {
    return $this->getHeight() > $this->getWidth() ?
      'portrait' :
      'landscape';
  }

  /**
   * {@inheritdoc}
   */
  public function getEmergencyEntity() {
    if (($item = $this->get('emergency_entity')) && isset($item[0])) {
      if ($entity = $item[0]->getValue()) {
        return ContentSetting::load($entity['target_id']);
      }
    }
    return NULL;

  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['extid'] = BaseFieldDefinition::create('string')
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setLabel(t('External ID'))
      ->setDescription(t('The external ID of the device entity.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 3,
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the device entity.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Status'))
      ->setDescription(t('A boolean indicating whether the device is enabled.'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'inline',
        'weight' => 4,
        'settings' => [
          'format' => 'on-off',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['needs_update'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Needs update'))
      ->setDescription(t('A boolean indicating whether the device needs a schedule update.'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', 'Yes')
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'inline',
        'weight' => 5,
        'settings' => [
          'format' => 'yes-no',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Description'))
      ->setDescription(t('A description of the device.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'inline',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the device was created.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 7,
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the device was last edited.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 8,
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['segments'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Device groups'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['digital_signage' => 'digital_signage']])
      ->setRequired(FALSE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
        'weight' => 10,
      ])
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['schedule'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Schedule'))
      ->setSetting('target_type', 'digital_signage_schedule')
      ->setRequired(FALSE)
      ->setCardinality(1)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'digital_signage_schedule_preview',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['size'] = BaseFieldDefinition::create('area_field_type')
      ->setLabel(t('Size'))
      ->setRequired(TRUE)
      ->setDefaultValue([
        'width' => '1920',
        'height' => '1080',
        'value' => '',
      ])
      ->setCardinality(1)
      ->setSettings([
        'width_precision' => '10',
        'width_scale' => '0',
        'height_precision' => '10',
        'height_scale' => '0',
        'value_precision' => '10',
        'value_scale' => '0',
        'width' => [
          'factor' => '1',
          'min' => '',
          'max' => '',
          'prefix' => '',
          'suffix' => '' ,
        ],
        'height' => [
          'factor' => '1',
          'min' => '',
          'max' => '',
          'prefix' => '',
          'suffix' => '' ,
        ],
        'value' => [
          'factor' => '1',
          'min' => '',
          'max' => '',
          'prefix' => '',
          'suffix' => '' ,
        ],
      ])
      ->setDisplayOptions('form', [
        'settings' => [],
        'weight' => 6,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['emergency_entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Emergency entity'))
      ->setDescription(t('The entity which is currently displayed for emergency mode on this device.'))
      ->setSetting('target_type', 'digital_signage_content_setting')
      ->setSettings([
        'handler' => 'views',
        'handler_settings' => [
          'view' => [
            'view_name' => 'emergency_entities',
            'display_name' => 'entity_reference_1',
            'arguments' => [],
          ],
        ],
      ])
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'settings' => [],
        'weight' => 10,
      ])
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'settings' => [
          'link' => FALSE,
        ],
        'label' => 'inline',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
