<?php

namespace Drupal\crm_core_activity\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\crm_core_activity\ActivityInterface;
use Drupal\crm_core_contact\ContactInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * CRM Activity Entity Class.
 *
 * @ContentEntityType(
 *   id = "crm_core_activity",
 *   label = @Translation("Activity"),
 *   bundle_label = @Translation("Activity type"),
 *   label_callback = "Drupal\crm_core_activity\Entity\Activity::defaultLabel",
 *   handlers = {
 *     "access" = "Drupal\crm_core_activity\ActivityAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\crm_core_activity\Form\ActivityForm",
 *       "delete" = "Drupal\crm_core_activity\Form\ActivityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\crm_core_activity\ActivityListBuilder",
 *     "views_data" = "Drupal\crm_core_activity\ActivityViewsData",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "route_provider" = {
 *        "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *      },
 *   },
 *   base_table = "crm_core_activity",
 *   admin_permission = "administer crm_core_activity entities",
 *   entity_keys = {
 *     "id" = "activity_id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   bundle_entity_type = "crm_core_activity_type",
 *   field_ui_base_route = "entity.crm_core_activity_type.edit_form",
 *   permission_granularity = "bundle",
 *   permission_labels = {
 *     "singular" = @Translation("Activity"),
 *     "plural" = @Translation("Activities"),
 *   },
 *   links = {
 *     "add-page" = "/crm-core/activity/add",
 *     "add-form" = "/crm-core/activity/add/{crm_core_activity_type}",
 *     "canonical" = "/crm-core/activity/{crm_core_activity}",
 *     "delete-form" = "/crm-core/activity/{crm_core_activity}/delete",
 *     "edit-form" = "/crm-core/activity/{crm_core_activity}/edit",
 *     "admin-form" = "/crm_core_activity.type_edit",
 *   }
 * )
 *
 * @todo Replace list builder with a view.
 */
class Activity extends ContentEntityBase implements ActivityInterface {

  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The activity type.'))
      ->setSetting('target_type', 'crm_core_activity_type')
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of this activity.'))
      ->setRequired(TRUE)
      ->setSetting('default_value', '')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the activity was created.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the activity was last edited.'));

    $fields['activity_participants'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Participants'))
      ->setSetting('exclude_entity_types', FALSE)
      ->setSetting('entity_type_ids', ['crm_core_individual', 'crm_core_organization'])
      ->setCardinality(-1)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'settings' => [
          'link' => TRUE,
        ],
        'type' => 'dynamic_entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'dynamic_entity_reference_default',
        'settings' => [
          'match_operator' => 'CONTAINS',
        ],
        'weight' => 0,
      ]);

    // @todo Check settings.
    $fields['activity_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date'))
      ->setDefaultValue(['default_date' => 'now'])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'settings' => [
          'format_type' => 'long',
        ],
        'type' => 'datetime_default',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 2,
      ]);

    // @todo Check settings.
    $fields['activity_notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notes'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 3,
        'settings' => [
          'rows' => 5,
        ],
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function addParticipant(ContactInterface $contact) {
    $this->get('activity_participants')->appendItem($contact);
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->changed;
  }

  /**
   * {@inheritdoc}
   */
  public function hasParticipant(ContactInterface $contact) {
    foreach ($this->activity_participants as $participant) {
      if ($participant->target_id === $contact->id()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->type->entity->getPlugin()->label($this);
  }

}
