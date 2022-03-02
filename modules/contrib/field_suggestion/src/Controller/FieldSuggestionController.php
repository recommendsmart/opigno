<?php

namespace Drupal\field_suggestion\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FieldSuggestionController.
 */
class FieldSuggestionController extends ControllerBase {

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $invalidator;

  /**
   * The helper.
   *
   * @var \Drupal\field_suggestion\Service\FieldSuggestionHelperInterface
   */
  protected $helper;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->invalidator = $container->get('cache_tags.invalidator');
    $instance->helper = $container->get('field_suggestion.helper');
    $instance->entityFieldManager = $container->get('entity_field.manager');

    return $instance;
  }

  /**
   * Pin values of selected fields at top of the suggestions list.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   * @param string $field_name
   *   The field name.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object that may be returned by the controller.
   */
  public function pin($entity_type_id, $entity_id, $field_name) {
    $entity_type_id = str_replace('-', '_', $entity_type_id);

    $value = $this->entityTypeManager()->getStorage($entity_type_id)
      ->load($entity_id)
      ->$field_name
      ->value;

    $field_type = $this->entityFieldManager
      ->getBaseFieldDefinitions($entity_type_id)[$field_name]
      ->getType();

    $storage = $this->entityTypeManager()->getStorage('field_suggestion');

    $entities = $storage->loadByProperties($values = [
      'type' => $field_type,
      'entity_type' => $entity_type_id,
      'field_name' => $field_name,
      'field_suggestion_' . $field_type => $value,
    ]);

    if (!empty($entities)) {
      $storage->delete($entities);
    }
    else {
      $storage->create($values)->save();
    }

    $this->invalidator->invalidateTags(['field_suggestion_operations']);

    return $this->redirect('<front>');
  }

  /**
   * Access check based on whether a field is supported or not.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   * @param string $field_name
   *   The field name.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access($entity_type_id, $entity_id, $field_name) {
    $config = $this->config('field_suggestion.settings');
    $field_names = (array) $config->get('fields');
    $entity_type_id = str_replace('-', '_', $entity_type_id);

    if (
      !empty($field_names[$entity_type_id]) &&
      in_array($field_name, $field_names[$entity_type_id])
    ) {
      $entity = $this->entityTypeManager()->getStorage($entity_type_id)
        ->load($entity_id);

      if ($entity !== NULL && !($field = $entity->$field_name)->isEmpty()) {
        $field_type = $this->entityFieldManager
          ->getBaseFieldDefinitions($entity_type_id)[$field_name]
          ->getType();

        $count = $this->entityTypeManager()->getStorage('field_suggestion')
          ->getQuery()
          ->condition('entity_type', $entity_type_id)
          ->condition('field_name', $field_name)
          ->condition('field_suggestion_' . $field_type, $field->value)
          ->range(0, 1)
          ->count()
          ->execute();

        return AccessResult::allowedIf(
          $count > 0 ||
          !in_array($field->value, $this->helper->ignored($entity_type_id, $field_name))
        );
      }
    }

    return AccessResult::neutral();
  }

}
