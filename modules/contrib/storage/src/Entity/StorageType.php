<?php

namespace Drupal\storage\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Storage type entity.
 *
 * @ConfigEntityType(
 *   id = "storage_type",
 *   label = @Translation("Storage type"),
 *   label_collection = @Translation("Storage types"),
 *   label_singular = @Translation("storage type"),
 *   label_plural = @Translation("storage types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count storage type",
 *     plural = "@count storage types",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\storage\StorageTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\storage\Form\StorageTypeForm",
 *       "edit" = "Drupal\storage\Form\StorageTypeForm",
 *       "delete" = "Drupal\storage\Form\StorageTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\storage\StorageTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "storage_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "storage",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/storage_types/{storage_type}",
 *     "add-form" = "/admin/structure/storage_types/add",
 *     "edit-form" = "/admin/structure/storage_types/{storage_type}/edit",
 *     "delete-form" = "/admin/structure/storage_types/{storage_type}/delete",
 *     "collection" = "/admin/structure/storage_types"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "description",
 *     "help",
 *     "new_revision",
 *   }
 * )
 */
class StorageType extends ConfigEntityBundleBase implements StorageTypeInterface {

  /**
   * The Storage type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Storage type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this node type.
   *
   * @var string
   */
  protected $description;

  /**
   * Help information shown to the user when creating storage data of this type.
   *
   * @var string
   */
  protected $help;

  /**
   * Default value of the 'Create new revision' checkbox of this storage type.
   *
   * @var bool
   */
  protected $new_revision = TRUE;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('node.type.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($new_revision) {
    $this->new_revision = $new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp() {
    return $this->help;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // if ($update && $this->getOriginalId() != $this->id()) {
    //   $update_count = node_type_update_nodes($this->getOriginalId(), $this->id());
    //   if ($update_count) {
    //     \Drupal::messenger()->addStatus(\Drupal::translation()->formatPlural(
    //       $update_count,
    //       'Changed the storage type of 1 post from %old-type to %type.',
    //       'Changed the storage type of @count posts from %old-type to %type.',
    //       [
    //         '%old-type' => $this->getOriginalId(),
    //         '%type' => $this->id(),
    //       ]
    //     ));
    //   }
    // }
    // if ($update) {
    //   // Clear the cached field definitions as some settings affect the field
    //   // definitions.
    //   \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
    // }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Clear the node type cache to reflect the removal.
    $storage->resetCache(array_keys($entities));
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    return $this->new_revision;
  }
}
