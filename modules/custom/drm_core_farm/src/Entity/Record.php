<?php

namespace Drupal\drm_core_farm\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\drm_core\EntityOwnerTrait;
use Drupal\drm_core_farm\RecordInterface;
use Drupal\entity\Revision\RevisionableContentEntityBase;

/**
 * DRM Record Entity Class.
 *
 * @ContentEntityType(
 *   id = "drm_core_individual",
 *   label = @Translation("DRM Core Record"),
 *   bundle_label = @Translation("Record type"),
 *   handlers = {
 *     "access" = "Drupal\drm_core_farm\RecordAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\drm_core_farm\Form\RecordForm",
 *       "delete" = "Drupal\drm_core_farm\Form\RecordDeleteForm",
 *     },
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\drm_core_farm\RecordListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *       "revision" = "\Drupal\entity\Routing\RevisionRouteProvider",
 *     },
 *   },
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer drm_core_record entities",
 *   base_table = "drm_core_record",
 *   revision_table = "drm_core_record_revision",
 *   entity_keys = {
 *     "id" = "record_id",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   bundle_entity_type = "drm_core_record_type",
 *   field_ui_base_route = "entity.drm_core_record_type.edit_form",
 *   permission_granularity = "bundle",
 *   permission_labels = {
 *     "singular" = @Translation("Record"),
 *     "plural" = @Translation("Records"),
 *   },
 *   links = {
 *     "add-page" = "/drm-core/record/add",
 *     "add-form" = "/drm-core/record/add/{drm_core_record_type}",
 *     "canonical" = "/drm-core/record/{drm_core_record}",
 *     "collection" = "/drm-core/record",
 *     "edit-form" = "/drm-core/record/{drm_core_record}/edit",
 *     "delete-form" = "/drm-core/record/{drm_core_record}/delete",
 *     "revision" = "/drm-core/record/{drm_core_record}/revisions/{drm_core_record_revision}/view",
 *     "revision-revert-form" = "/drm-core/record/{drm_core_record}/revisions/{drm_core_record_revision}/revert",
 *     "version-history" = "/drm-core/record/{drm_core_record}/revisions",
 *   }
 * )
 */
class Record extends RevisionableContentEntityBase implements RecordInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the record was created.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the record was last edited.'))
      ->setRevisionable(TRUE);

    $fields['uid'] = EntityOwnerTrait::getOwnerFieldDefinition()
      ->setDescription(t('The user that is the record owner.'));

    $fields['name'] = BaseFieldDefinition::create('name')
      ->setLabel(t('Name'))
      ->setDescription(t('Name of the record.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'name_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'name_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Gets the primary address.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|\Drupal\Core\TypedData\TypedDataInterface
   *   The address property object.
   */
  public function getPrimaryAddress() {
    return $this->getPrimaryField('address');
  }

  /**
   * Gets the primary email.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|\Drupal\Core\TypedData\TypedDataInterface
   *   The email property object.
   */
  public function getPrimaryEmail() {
    return $this->getPrimaryField('email');
  }

  /**
   * Gets the primary phone.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|\Drupal\Core\TypedData\TypedDataInterface
   *   The phone property object.
   */
  public function getPrimaryPhone() {
    return $this->getPrimaryField('phone');
  }

  /**
   * Gets the primary field.
   *
   * @param string $field
   *   The primary field name.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|\Drupal\Core\TypedData\TypedDataInterface
   *   The primary field property object.
   *
   * @throws \InvalidArgumentException
   *   If no primary field is configured.
   *   If the configured primary field does not exist.
   */
  public function getPrimaryField($field) {
    $type = $this->get('type')->entity;
    $name = empty($type->primary_fields[$field]) ? '' : $type->primary_fields[$field];
    return $this->get($name);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $label = '';
    if ($item = $this->get('name')->first()) {
      $label = "$item->given $item->family";
    }
    if (empty(trim($label))) {
      $label = t('Nameless #@id', ['@id' => $this->id()]);
    }
    \Drupal::moduleHandler()->alter('drm_core_record_label', $label, $this);

    return $label;
  }

}
