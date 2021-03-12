<?php

namespace Drupal\crm_core_contact\Menu;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\Menu\DefaultEntityLocalTaskProvider;

/**
 * Provides a set of tasks to view, edit and duplicate an entity.
 */
class ContactLocalTaskProvider extends DefaultEntityLocalTaskProvider {

  /**
   * {@inheritdoc}
   */
  public function buildLocalTasks(EntityTypeInterface $entity_type) {
    // See #1834002 and #3044371 for context.
    $link_templates = [];
    $types = [
      'canonical',
      'edit-form',
      'duplicate-form',
      'delete-form',
      'version-history',
    ];
    foreach ($types as $type) {
      if ($entity_type->hasLinkTemplate($type)) {
        $link_templates[] = str_replace('-', '_', $type);
      }
    }

    $tasks = [];
    if (count($link_templates) > 1) {
      $entity_type_id = $entity_type->id();
      $base = reset($link_templates);

      $titles = [
        'canonical' => $this->t('View'),
        'edit_form' => $this->t('Edit'),
        'duplicate_form' => $this->t('Duplicate'),
        'delete_form' => $this->t('Delete'),
        'version_history' => $this->t('Revisions'),
      ];

      $weight = 0;
      foreach ($link_templates as $template) {
        $route_name = "entity.$entity_type_id.$template";
        $tasks[$route_name] = [
          'title' => $titles[$template],
          'route_name' => $route_name,
          'base_route' => "entity.$entity_type_id.$base",
          'weight' => $weight,
        ];

        $weight += 10;
      }
    }
    return $tasks;
  }

}
