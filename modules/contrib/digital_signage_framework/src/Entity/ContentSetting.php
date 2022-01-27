<?php

namespace Drupal\digital_signage_framework\Entity;

use Drupal;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\digital_signage_framework\ContentSettingInterface;

/**
 * Defines the digital signage content setting entity class.
 *
 * @ContentEntityType(
 *   id = "digital_signage_content_setting",
 *   label = @Translation("Digital signage content setting"),
 *   label_collection = @Translation("Digital signage content settings"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData"
 *   },
 *   base_table = "digital_signage_content_setting",
 *   admin_permission = "administer digital signage content setting",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   field_ui_base_route = "entity.digital_signage_content_setting.settings"
 * )
 */
class ContentSetting extends ContentEntityBase implements ContentSettingInterface {

  /**
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *
   * @return string
   */
  private function getHash(ContentEntityBase $entity): string {
    $fields = ['status', 'auto_label', 'label', 'predecessor', 'emergencymode', 'dynamic', 'critical', 'priority', 'type', 'count', 'devices', 'segments'];
    $value = '';
    foreach ($fields as $field) {
      $value .= serialize($entity->get($field)->getValue());
    }
    return hash('md5', $value);
  }

  /**
   * {@inheritdoc}
   */
  public function hasChanged(): bool {
    return Drupal::state()->get('DigSigContentSetting-prev-' . $this->id()) !== $this->getHash($this);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    Drupal::state()->set('DigSigContentSetting-prev-' . $this->id(), isset($this->original) ? $this->getHash($this->original) : '');
    parent::postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function getReverseEntity() {
    if (($item = $this->get('parent_entity')) && isset($item[0])) {
      $reverse_entity = $item[0]->getValue();
      if (!empty($reverse_entity['target_id'])) {
        return $reverse_entity;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setReverseEntity($entity): ContentSettingInterface {
    $this->set('parent_entity', $entity);
    $this->set('parent_entity_bundle', $entity->bundle());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReverseEntityType() {
    $entity = $this->getReverseEntity();
    return $entity ? $entity['target_type'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getReverseEntityBundle() {
    return $this->get('parent_entity_bundle')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getReverseEntityId(): int {
    $entity = $this->getReverseEntity();
    return $entity ? $entity['target_id'] : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function isReverseEntityEnabled(): bool {
    return (bool) $this->get('parent_entity_status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setReverseEntityStatus($status): ContentSettingInterface {
    $this->set('parent_entity_status', $status);
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
  public function setStatus($status): ContentSettingInterface {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeviceIds(): array {
    $ids = [];
    /** @var \Drupal\digital_signage_framework\DeviceInterface $entity */
    foreach ($this->get('devices')->referencedEntities() as $entity) {
      $ids[] = $entity->id();
    }
    return $ids;
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
  public function getPriority(): int {
    return $this->get('priority')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isCritical(): bool {
    return (bool) $this->get('critical')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isDynamic(): bool {
    return (bool) $this->get('dynamic')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDynamic($flag): ContentSettingInterface {
    $this->set('dynamic', $flag);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    return $this->get('type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isAutoLabel(): bool {
    return (bool) $this->get('auto_label')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->get('label')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label): ContentSettingInterface {
    $this->set('label', $label);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Publish'))
      ->setDescription(t('Enable to publish this content to digital signage platforms.'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['auto_label'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Automatic label'))
      ->setDescription(t('Use the label from the parent when enabled.'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);
    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(FALSE)
      ->setCardinality(1)
      ->setDisplayOptions('form', [
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['predecessor'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Predecessor'))
      ->setSetting('target_type', 'digital_signage_content_setting')
      ->setRequired(FALSE)
      ->setCardinality(1)
      ->setSettings([
        'handler' => 'views',
        'handler_settings' => [
          'view' => [
            'view_name' => 'digital_signage_predecessor',
            'display_name' => 'entity_reference_1',
            'arguments' => [],
          ],
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 3,
      ])
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['emergencymode'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Emergency mode'))
      ->setDescription(t('Enable to mark this entity as content for emergency mode.'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['dynamic'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Dynamic content'))
      ->setDescription(t('If checked, the content will be updated dynamically by the remote displays without creating new schedules every time this content changes.'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', 'Dynamic content')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['critical'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Critical'))
      ->setDescription(t('Critical information can be treated separately by schedules/playlist.'))
      ->setDefaultValue(FALSE)
      ->setSetting('on_label', 'Critical')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    /** @noinspection PackedHashtableOptimizationInspection */
    $fields['priority'] = BaseFieldDefinition::create('list_integer')
      ->setRevisionable(TRUE)
      ->setLabel(t('Priority'))
      ->setDescription(t('The incluences the usage by schedules/playlists.'))
      ->setDefaultValue('2')
      ->setSetting('allowed_values', [
        '1' => t('high'),
        '2' => t('normal'),
        '3' => t('low'),
      ])
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setRevisionable(TRUE)
      ->setLabel(t('Type'))
      ->setDescription(t('The type may influence the usage of this content in schedules/playlists.'))
      ->setDefaultValue('simple')
      ->setSetting('allowed_values', [
        'simple' => t('Simple'),
        'complex' => t('Complex'),
      ])
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['count'] = BaseFieldDefinition::create('integer')
      ->setRevisionable(TRUE)
      ->setLabel(t('Count'))
      ->setDescription(t('How often this content should be displayed per device, leave empty for unlimited.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['devices'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Devices'))
      ->setSetting('target_type', 'digital_signage_device')
      ->setRequired(FALSE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
        'weight' => 98,
      ])
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
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
        'weight' => 99,
      ])
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['parent_entity'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setRevisionable(FALSE)
      ->setLabel(t('Parent entity'))
      ->setDescription(t('The entity of the content which contains these settings.'));
    $fields['parent_entity_bundle'] = BaseFieldDefinition::create('string')
      ->setRevisionable(FALSE)
      ->setLabel(t('Parent entity bundle'))
      ->setDescription(t('The entity bundle of the content which contains these settings.'));
    $fields['parent_entity_status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Parent entity status'))
      ->setDescription(t('The entity status of the content which contains these settings.'));

    return $fields;
  }

}
