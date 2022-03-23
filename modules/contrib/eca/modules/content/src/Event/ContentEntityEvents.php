<?php

namespace Drupal\eca_content\Event;

/**
 * Contains all events triggered by Calunda module regarding content entities.
 */
final class ContentEntityEvents {

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityBundleCreate event.
   */
  public const BUNDLECREATE = 'eca.content_entity.bundlecreate';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityBundleDelete event.
   */
  public const BUNDLEDELETE = 'eca.content_entity.bundledelete';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityCreate event.
   */
  public const CREATE = 'eca.content_entity.create';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityRevisionCreate event.
   */
  public const REVISIONCREATE = 'eca.content_entity.revisioncreate';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityPreLoad event.
   */
  public const PRELOAD = 'eca.content_entity.preload';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityLoad event.
   */
  public const LOAD = 'eca.content_entity.load';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityStorageLoad event.
   */
  public const STORAGELOAD = 'eca.content_entity.storageload';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityPreSave event.
   */
  public const PRESAVE = 'eca.content_entity.presave';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityInsert event.
   */
  public const INSERT = 'eca.content_entity.insert';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityUpdate event.
   */
  public const UPDATE = 'eca.content_entity.update';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityTranslationCreate event.
   */
  public const TRANSLATIONCREATE = 'eca.content_entity.translationcreate';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityTranslationInsert event.
   */
  public const TRANSLATIONINSERT = 'eca.content_entity.translationsinsert';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityTranslationDelete event.
   */
  public const TRANSLATIONDELETE = 'eca.content_entity.translationdelete';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityPreDelete event.
   */
  public const PREDELETE = 'eca.content_entity.predelete';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityDelete event.
   */
  public const DELETE = 'eca.content_entity.delete';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityRevisionDelete event.
   */
  public const REVISIONDELETE = 'eca.content_entity.revisiondelete';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityView event.
   */
  public const VIEW = 'eca.content_entity.view';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityPrepareView event.
   */
  public const PREPAREVIEW = 'eca.content_entity.prepareview';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityPrepareForm event.
   */
  public const PREPAREFORM = 'eca.content_entity.prepareform';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityFieldValuesInit event.
   */
  public const FIELDVALUESINIT = 'eca.content_entity.fieldvaluesinit';

  /**
   * Identifier for the \Drupal\eca_content\Event\ContentEntityCustomEvent event.
   */
  public const CUSTOM = 'eca.content_entity.custom';

}
