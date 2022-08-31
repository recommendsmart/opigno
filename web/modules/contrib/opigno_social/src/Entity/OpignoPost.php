<?php

namespace Drupal\opigno_social\Entity;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\opigno_like\Services\OpignoLikeManager;
use Drupal\opigno_social\Services\OpignoPostsManager;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Define the Opigno post/comment entity.
 *
 * @ingroup opigno_social
 *
 * @ContentEntityType(
 *   id = "opigno_post",
 *   label = @Translation("Opigno post/comment"),
 *   base_table = "opigno_post",
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\opigno_social\OpignoPostViewsData",
 *     "access" = "Drupal\opigno_social\OpignoPostAccessControlHandler",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uid" = "uid",
 *     "parent" = "parent",
 *     "created" = "created",
 *   },
 *   links = {
 *     "canonical" = "/post/{opigno_post}",
 *   },
 * )
 */
class OpignoPost extends ContentEntityBase implements OpignoPostInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['text'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Text'))
      ->setRequired(TRUE);

    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Parent')
      ->setSetting('target_type', 'opigno_post')
      ->setDefaultValue(0);

    // Group/module ID.
    $fields['attachment_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Attachment entity ID'));

    // Attachment entity type: group/opigno_module.
    $fields['attachment_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Attachment entity type'));

    // Training/certificate/badge.
    $fields['attachment_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Attachment type'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorId(): int {
    return (int) $this->get('uid')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthor(): ?UserInterface {
    $uid = $this->getAuthorId();
    if (!$uid) {
      return NULL;
    }
    $user = User::load($uid);

    return $user instanceof UserInterface ? $user : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthorId(int $uid): OpignoPostInterface {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentId(): int {
    return (int) $this->get('parent')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function setParentId(int $pid): OpignoPostInterface {
    $this->set('parent', $pid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent(): ?OpignoPostInterface {
    $id = $this->getParentId();
    if (!$id) {
      return NULL;
    }
    $parent = static::load($id);

    return $parent instanceof OpignoPostInterface || !$parent->isComment() ? $parent : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getText(): string {
    return $this->get('text')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function setText(string $text): OpignoPostInterface {
    $this->set('text', $text);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return (int) $this->get('created')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function isComment(): bool {
    return (bool) $this->getParentId();
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachmentEntityType(): string {
    return $this->get('attachment_entity_type')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function setAttachmentEntityType(string $type): OpignoPostInterface {
    $this->set('attachment_entity_type', $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachmentEntityId(): int {
    return (int) $this->get('attachment_entity_id')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachmentEntity(): ?EntityInterface {
    $eid = $this->getAttachmentEntityId();
    $type = $this->getAttachmentEntityType();
    if (!$eid || !$type) {
      return NULL;
    }

    try {
      $entity = $this->entityTypeManager()->getStorage($type)->load($eid);
    }
    catch (PluginNotFoundException | InvalidPluginDefinitionException $e) {
      watchdog_exception('opigno_social_exception', $e);
      $entity = NULL;
    }

    return $entity instanceof EntityInterface ? $entity : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setAttachmentEntityId(int $eid): OpignoPostInterface {
    // Attachments can be added only to posts, not to comments.
    if (!$this->isComment()) {
      $this->set('attachment_entity_id', $eid);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachmentType(): string {
    return $this->get('attachment_type')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function setAttachmentType(string $type): OpignoPostInterface {
    // Attachments can be added only to posts, not to comments.
    if (!$this->isComment()) {
      $this->set('attachment_type', $type);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Set the current user as the author if none was set before.
    if (!$this->getAuthorId()) {
      $uid = (int) \Drupal::currentUser()->id();
      $this->setAuthorId($uid);
    }

    // Save the attachment only if the allowed type was given.
    $attachment_type = $this->getAttachmentType();
    $allowed = [
      'training',
      'certificate',
      'badge',
    ];
    $attachment_entity_type = $this->getAttachmentEntityType();
    $entity_types = ['group', 'opigno_module'];

    if (($attachment_type && !in_array($attachment_type, $allowed))
      || ($attachment_entity_type && !in_array($attachment_entity_type, $entity_types))
    ) {
      $this->setAttachmentType('');
      $this->setAttachmentEntityId(0);
      $this->setAttachmentEntityType('');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Create the notification for the post author if someone commented a post.
    if ($update) {
      return;
    }

    $post = $this->getParent();
    if (!$post instanceof OpignoPostInterface || $post->getAuthorId() === (int) \Drupal::currentUser()->id()) {
      return;
    }

    $author = $this->getAuthor();
    if (!$author instanceof UserInterface) {
      return;
    }

    $msg = $this->t('@user replied to your post', ['@user' => $author->getDisplayName()]);
    $url = Url::fromRoute('entity.opigno_post.canonical', ['opigno_post' => (int) $post->id()])->toString();
    $url = $url instanceof GeneratedUrl ? $url->getGeneratedUrl() : $url;

    try {
      opigno_set_message($post->getAuthorId(), $msg, $url);
    }
    catch (EntityStorageException $e) {
      watchdog_exception('opigno_social_exception', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return array_merge(parent::getCacheContexts(), ['user', 'url']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    $tags = parent::getCacheTagsToInvalidate();
    return array_merge($tags, [OpignoLikeManager::ENTITY_LIKERS_CACHE_PREFIX . $this->entityTypeId . (int) $this->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();

    $like_manager = \Drupal::service('opigno_like.manager');
    $post_manager = \Drupal::service('opigno_posts.manager');
    if (!$like_manager instanceof OpignoLikeManager || !$post_manager instanceof OpignoPostsManager) {
      return;
    }

    // Remove likes.
    $id = (int) $this->id();
    $post_manager->cache->delete(OpignoPostsManager::OPIGNO_POST_COMMENTS_CACHE_PREFIX . $id);
    $like_manager->deleteEntityLikes($id, $this->getEntityTypeId());

    if ($this->isComment()) {
      // Invalidate cache.
      $post_manager->cache->invalidate(OpignoPostsManager::OPIGNO_POST_COMMENTS_CACHE_PREFIX . $this->getParentId());
      return;
    }

    // Remove post comments.
    $comment_ids = $post_manager->getPostComments($id);
    $comments = $post_manager->loadPost($comment_ids);
    if (!$comments) {
      return;
    }

    foreach ($comments as $comment) {
      if (!$comment instanceof OpignoPostInterface) {
        continue;
      }
      try {
        $comment->delete();
      }
      catch (EntityStorageException $e) {
        watchdog_exception('opigno_social_exception', $e);
      }
    }
  }

}
