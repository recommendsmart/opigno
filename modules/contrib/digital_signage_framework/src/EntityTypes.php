<?php

namespace Drupal\digital_signage_framework;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class EntityTypes {

  public const EXCLUDED = [
    'aggregator_feed',
    'aggregator_item',
    'block_content',
    'comment',
    'commerce_product_variation_type',
    'contact_message',
    'content_moderation_state',
    'crop',
    'danse_event',
    'danse_notification',
    'danse_notification_action',
    'digital_signage_content_setting',
    'digital_signage_device',
    'digital_signage_schedule',
    'entity_embed_fake_entity',
    'helpdesk_issue',
    'menu_link_content',
    'onesignal_device',
    'paragraph',
    'paragraphs_library_item',
    'path_alias',
    'redirect',
    'shortcut',
    'smart_date_override',
    'smart_date_rule',
    'wayfinding',
    'webform_submission',
    'workspace',
  ];


  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Renderer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   */
  public function all(): array {
    return $this->entityTypeManager->getDefinitions();
  }

  /**
   * @return string[]
   */
  public function allIds(): array {
    return array_keys($this->all());
  }

  /**
   * @return string[]
   */
  public function allEnabledIds(): array {
    return array_diff($this->allIds(), $this->allDisabledIds());
  }

  /**
   * @return string[]
   */
  public function allDisabledIds(): array {
    // TODO: Allow other modules to alter this list.
    return self::EXCLUDED;
  }

}
