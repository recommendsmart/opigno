<?php

namespace Drupal\arch_addressbook\Entity;

use Drupal\arch_addressbook\AddressbookitemInterface;
use Drupal\arch_order\OrderAddressData;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\user\UserInterface;

/**
 * Defines the AddressBookItem entity.
 *
 * This is the main definition of the entity type. From it, an entityType is
 * derived. The most important properties in this example are listed below.
 *
 * id: The unique identifier of this entityType. It follows the pattern
 * 'moduleName_xyz' to avoid naming conflicts.
 *
 * label: Human readable name of the entity type.
 *
 * handlers: Handler classes are used for different tasks. You can use
 * standard handlers provided by D8 or build your own, most probably derived
 * from the standard class. In detail:
 *
 * - view_builder: we use the standard controller to view an instance. It is
 *   called when a route lists an '_entity_view' default for the entityType
 *   (see routing.yml for details. The view can be manipulated by using the
 *   standard drupal tools in the settings.
 *
 * - list_builder: We derive our own list builder class from the
 *   entityListBuilder to control the presentation.
 *   If there is a view available for this entity from the views module, it
 *   overrides the list builder. @todo any view? naming convention?
 *
 * - form: We derive our own forms to add functionality like additional fields,
 *   redirects etc. These forms are called when the routing list an
 *   '_entity_form' default for the entityType. Depending on the suffix
 *   (.add/.edit/.delete) in the route, the correct form is called.
 *
 * - access: Our own accessController where we determine access rights based on
 *   permissions.
 *
 * More properties:
 *
 *  - base_table: Define the name of the table used to store the data. Make sure
 *    it is unique. The schema is automatically determined from the
 *    BaseFieldDefinitions below. The table is automatically created during
 *    installation.
 *
 *  - fieldable: Can additional fields be added to the entity via the GUI?
 *    Analog to content types.
 *
 *  - entity_keys: How to access the fields. Analog to 'nid' or 'uid'.
 *
 *  - links: Provide links to do standard tasks. The 'edit-form' and
 *    'delete-form' links are added to the list built by the
 *    entityListController. They will show up as action buttons in an additional
 *    column.
 *
 * There are many more properties to be used in an entity type definition. For
 * a complete overview, please refer to the '\Drupal\Core\Entity\EntityType'
 * class definition.
 *
 * The following construct is the actual definition of the entity type which
 * is read and cached. Don't forget to clear cache after changes.
 *
 * @ContentEntityType(
 *   id = "addressbookitem",
 *   label = @Translation("Address"),
 *   label_collection = @Translation("Addresses"),
 *   label_singular = @Translation("address"),
 *   label_plural = @Translation("addresses"),
 *   label_count = @PluralTranslation(
 *     singular = "@count address",
 *     plural = "@count addresses"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\arch_addressbook\AddressbookitemAccessControlHandler",
 *     "list_builder" = "Drupal\arch_addressbook\Entity\Controller\AddressbookitemListBuilder",
 *     "view_builder" = "Drupal\arch_addressbook\Entity\AddressbookitemViewBuilder",
 *     "views_data" = "Drupal\arch_addressbook\Entity\Views\AddressbookitemViewsData",
 *     "form" = {
 *       "add" = "Drupal\arch_addressbook\Form\AddressbookitemForm",
 *       "edit" = "Drupal\arch_addressbook\Form\AddressbookitemForm",
 *       "delete" = "Drupal\arch_addressbook\Form\AddressbookitemDeleteForm",
 *       "default" = "Drupal\arch_addressbook\Form\AddressbookitemForm"
 *     }
 *   },
 *   admin_permission = "administer addressbookitem entity",
 *   base_table = "addressbookitem",
 *   revision_table = "addressbookitem_revision",
 *   show_revision_ui = TRUE,
 *   common_reference_target = TRUE,
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   links = {
 *     "canonical" = "/address/{addressbookitem}",
 *     "edit-form" = "/address/{addressbookitem}/edit",
 *     "delete-form" = "/address/{addressbookitem}/delete",
 *     "collection" = "/address/list"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid"
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   field_ui_base_route = "addressbookitem.settings"
 * )
 *
 * The 'links' above are defined by their path. For core to find the
 * corresponding route, the route name must follow the correct pattern:
 *
 * entity.<entity-name>.<link-name> (replace dashes with underscores)
 * Example: 'entity.addressbookitem.canonical'
 *
 * See routing file above for the corresponding implementation
 *
 * The 'Addressbookitem' class defines methods and fields for the
 * Addressbookitem entity.
 *
 * Being derived from the ContentEntityBase class, we can override the methods
 * we want. In our case we want to provide access to the standard fields about
 * creation and changed time stamps.
 *
 * Our interface (see ContactInterface) also exposes the EntityOwnerInterface.
 * This allows us to provide methods for setting and providing ownership
 * information.
 *
 * The most important part is the definitions of the field properties for this
 * entity type. These are of the same type as fields added through the GUI, but
 * they can by changed in code. In the definition we can define if the user with
 * the rights privileges can influence the presentation (view, edit) of each
 * field.
 */
class Addressbookitem extends RevisionableContentEntityBase implements AddressbookitemInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID', [], ['context' => 'arch_addressbook']))
      ->setDescription(t('The ID of the Addressbookitem entity.', [], ['context' => 'arch_addressbook']))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label', [], ['context' => 'arch_addressbook']))
      ->setRequired(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Owner field of the contact.
    // Entity reference field, holds the reference to the user object.
    // The view shows the user name field of the user.
    // The form presents a auto complete field for the user name.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User', [], ['context' => 'arch_addressbook']))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDescription(t('Start typing the email address of the desired user.', [], ['context' => 'arch_addressbook']))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created', [], ['context' => 'arch_addressbook']))
      ->setRevisionable(TRUE)
      ->setDescription(t('The time that this address was created.', [], ['context' => 'arch_addressbook']))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'settings' => [
          'date_format' => 'custom',
          'custom_date_format' => 'Y-m-d H:i:s',
          'timezone' => '',
        ],
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed', [], ['context' => 'arch_addressbook']))
      ->setRevisionable(TRUE)
      ->setDescription(t('The time that this address was last edited.', [], ['context' => 'arch_addressbook']))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'settings' => [
          'date_format' => 'custom',
          'custom_date_format' => 'Y-m-d H:i:s',
          'timezone' => '',
        ],
        'weight' => 11,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByProperties(array $values = []) {
    $entity_type_repository = \Drupal::service('entity_type.repository');
    $entity_type_manager = \Drupal::entityTypeManager();
    $storage = $entity_type_manager->getStorage($entity_type_repository->getEntityTypeFromClass(get_called_class()));
    return $storage->loadByProperties($values);
  }

  /**
   * {@inheritdoc}
   */
  public function toOrderAddress() {
    $data = $this->get('address')->first()->getValue();
    return new OrderAddressData($data);
  }

}
