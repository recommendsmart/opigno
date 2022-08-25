<?php

namespace Drupal\content_to_group\Util;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class ContentToGroupUtility {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * Get the content available types.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getContentTypes() {
    $types = $this->entityTypeManager->getStorage('node_type')
      ->loadMultiple();
    $options = [];
    foreach ($types as $node_type) {
      $options[$node_type->id()] = $node_type->label();
    }

    return $options;
  }

  /**
   * Determine which node field is an entity reference to a group entity.
   */
  public function getGroupField($node) {
    $group_field = NULL;
    foreach ($node->getFieldDefinitions() as $field_name => $field) {
      if ($field->getType() == 'entity_reference' && $field->getSetting('target_type') == 'group') {
        $group_field = $field_name;
        break;
      }
    }
    return $group_field;
  }

}
