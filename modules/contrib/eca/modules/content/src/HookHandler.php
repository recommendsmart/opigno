<?php

namespace Drupal\eca_content;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\eca\Event\BaseHookHandler;

/**
 * The handler for hook implementations within the eca_content.module file.
 */
class HookHandler extends BaseHookHandler {

  /**
   * @param string $entity_type_id
   * @param string $bundle
   */
  public function bundleCreate(string $entity_type_id, string $bundle): void {
    $this->triggerEvent->dispatchFromPlugin('content_entity:bundlecreate', $entity_type_id, $bundle);
  }

  /**
   * @param string $entity_type_id
   * @param string $bundle
   */
  public function bundleDelete(string $entity_type_id, string $bundle): void {
    $this->triggerEvent->dispatchFromPlugin('content_entity:bundledelete', $entity_type_id, $bundle);
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function create(EntityInterface $entity): void {
    if ($entity instanceof ContentEntityInterface) {
      $this->triggerEvent->dispatchFromPlugin('content_entity:create', $entity);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $new_revision
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param bool|null $keep_untranslatable_fields
   */
  public function revisionCreate(EntityInterface $new_revision, EntityInterface $entity, ?bool $keep_untranslatable_fields): void {
    if ($new_revision instanceof ContentEntityInterface && $entity instanceof ContentEntityInterface) {
      $this->triggerEvent->dispatchFromPlugin('content_entity:revisioncreate', $new_revision, $entity, $keep_untranslatable_fields);
    }
  }

  /**
   * @param array $ids
   * @param string $entity_type_id
   */
  public function preload(array $ids, string $entity_type_id): void {
    $this->triggerEvent->dispatchFromPlugin('content_entity:preload', $ids, $entity_type_id);
  }

  /**
   * @param array $entities
   * @param string $entity_type_id
   */
  public function load(array $entities, string $entity_type_id): void {
    foreach ($entities as $entity) {
      if ($entity instanceof ContentEntityInterface) {
        $this->triggerEvent->dispatchFromPlugin('content_entity:load', $entity);
      }
    }
  }

  /**
   * @param array $entities
   * @param string $entity_type
   */
  public function storageLoad(array $entities, string $entity_type): void {
    foreach ($entities as $entity) {
      if ($entity instanceof ContentEntityInterface) {
        $this->triggerEvent->dispatchFromPlugin('content_entity:storageload', $entity);
      }
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function presave(EntityInterface $entity): void {
    if ($entity instanceof ContentEntityInterface) {
      $this->triggerEvent->dispatchFromPlugin('content_entity:presave', $entity);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function insert(EntityInterface $entity): void {
    if ($entity instanceof ContentEntityInterface) {
      $this->triggerEvent->dispatchFromPlugin('content_entity:insert', $entity);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function update(EntityInterface $entity): void {
    if ($entity instanceof ContentEntityInterface) {
      if ($entity->getEntityType()->hasKey('revision')) {
        // Make sure the subsequent actions will not create another revision
        // when they save this entity again.
        $entity->setNewRevision(FALSE);
        $entity->updateLoadedRevisionId();
      }
      $this->triggerEvent->dispatchFromPlugin('content_entity:update', $entity);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $translation
   */
  public function translationCreate(EntityInterface $translation): void {
    if ($translation instanceof ContentEntityInterface) {
      $this->triggerEvent->dispatchFromPlugin('content_entity:translationcreate', $translation);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $translation
   */
  public function translationInsert(EntityInterface $translation): void {
    if ($translation instanceof ContentEntityInterface) {
      $this->triggerEvent->dispatchFromPlugin('content_entity:translationinsert', $translation);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $translation
   */
  public function translationDelete(EntityInterface $translation): void {
    if ($translation instanceof ContentEntityInterface) {
      $this->triggerEvent->dispatchFromPlugin('content_entity:translationdelete', $translation);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function predelete(EntityInterface $entity): void {
    if ($entity instanceof ContentEntityInterface) {
      $this->triggerEvent->dispatchFromPlugin('content_entity:predelete', $entity);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function delete(EntityInterface $entity): void {
    if ($entity instanceof ContentEntityInterface) {
      $this->triggerEvent->dispatchFromPlugin('content_entity:delete', $entity);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function revisionDelete(EntityInterface $entity): void {
    if ($entity instanceof ContentEntityInterface) {
      $this->triggerEvent->dispatchFromPlugin('content_entity:revisiondelete', $entity);
    }
  }

  /**
   * @param array $build
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   * @param string $view_mode
   */
  public function view(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, string $view_mode): void {
    if ($entity instanceof ContentEntityInterface) {
      $this->triggerEvent->dispatchFromPlugin('content_entity:view', $entity);
    }
  }

  /**
   * @param string $entity_type_id
   * @param array $entities
   * @param array $displays
   * @param string $view_mode
   */
  public function prepareView(string $entity_type_id, array $entities, array $displays, string $view_mode): void {
    foreach ($entities as $entity) {
      if ($entity instanceof ContentEntityInterface) {
        $this->triggerEvent->dispatchFromPlugin('content_entity:prepareview', $entity, $displays, $view_mode);
      }
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $operation
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function prepareForm(EntityInterface $entity, string $operation, FormStateInterface $form_state): void {
    if ($entity instanceof ContentEntityInterface) {
      $this->triggerEvent->dispatchFromPlugin('content_entity:prepareform', $entity, $operation, $form_state);
    }
  }

  /**
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   */
  public function fieldValuesInit(FieldableEntityInterface $entity): void {
    if ($entity instanceof ContentEntityInterface) {
      $this->triggerEvent->dispatchFromPlugin('content_entity:fieldvaluesinit', $entity);
    }
  }

}
