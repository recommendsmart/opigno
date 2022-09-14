<?php

namespace Drupal\votingapi_widgets;

use Drupal\votingapi_widgets\Plugin\VotingApiWidgetManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Implements lazy loading.
 */
class VotingApiLoader implements TrustedCallbackInterface {

  /**
   * The votingapi_widget widget manager.
   *
   * @var \Drupal\votingapi_widgets\Plugin\VotingApiWidgetManager
   */
  protected $widgetManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The VotingApiLoader constructor.
   *
   * @param \Drupal\votingapi_widgets\Plugin\VotingApiWidgetManager $widget_manager
   *   The votingapi_widget widget manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(VotingApiWidgetManager $widget_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->widgetManager = $widget_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Build rate form.
   */
  public function buildForm($plugin_id, $entity_type, $entity_bundle, $entity_id, $vote_type, $field_name, $settings) {
    $definitions = $this->widgetManager->getDefinitions();
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    $plugin = $this->widgetManager->createInstance($plugin_id, $definitions[$plugin_id]);
    $fieldDefinition = $entity->{$field_name}->getFieldDefinition();
    if (empty($plugin) || empty($entity) || !$entity->hasField($field_name)) {
      return [];
    }
    return $plugin->buildForm($entity_type, $entity_bundle, $entity_id, $vote_type, $field_name, unserialize($settings));
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['buildForm'];
  }

}
