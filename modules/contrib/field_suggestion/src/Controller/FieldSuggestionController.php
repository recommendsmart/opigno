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
   * Permission per action type.
   */
  const PERMISSIONS = [
    'pin' => 'pin and unpin field suggestion',
    'ignore' => 'ignore field suggestion',
  ];

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
   * Pin or ignore values of selected fields.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param int $entity_id
   *   The entity ID.
   * @param string $field_name
   *   The field name.
   * @param string $type
   *   The action type.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object that may be returned by the controller.
   */
  public function action($entity_type_id, $entity_id, $field_name, $type) {
    $entity_type_id = str_replace('-', '_', $entity_type_id);

    /** @var \Drupal\Core\Field\BaseFieldDefinition $definition */
    $definition = $this->entityFieldManager
      ->getBaseFieldDefinitions($entity_type_id)[$field_name];

    $property = $definition->getMainPropertyName() ?? 'value';

    $value = $this->entityTypeManager()->getStorage($entity_type_id)
      ->load($entity_id)
      ->$field_name
      ->$property;

    $storage = $this->entityTypeManager()->getStorage('field_suggestion');

    $entities = $storage->loadByProperties($values = [
      'type' => $field_type = $definition->getType(),
      'ignore' => $type === 'ignore',
      'entity_type' => $entity_type_id,
      'field_name' => $field_name,
      $this->helper->field($field_type) => $value,
    ]);

    if (!empty($entities)) {
      $storage->delete($entities);
    }
    else {
      $values['ignore'] = !$values['ignore'];
      $entities = $storage->loadByProperties($values);
      $values['ignore'] = !$values['ignore'];

      if (!empty($entities)) {
        /** @var \Drupal\field_suggestion\FieldSuggestionInterface $entity */
        foreach ($entities as $entity) {
          $entity->setIgnored($values['ignore'])->save();
        }
      }
      else {
        $storage->create($values)->save();
      }
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
   * @param string $type
   *   The action type.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access($entity_type_id, $entity_id, $field_name, $type) {
    if (!(
      isset(self::PERMISSIONS[$type]) &&
      (
        $this->currentUser()->hasPermission('administer field suggestion') ||
        $this->currentUser()->hasPermission(self::PERMISSIONS[$type])
      )
    )) {
      return AccessResult::neutral();
    }

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
        /** @var \Drupal\Core\Field\BaseFieldDefinition $definition */
        $definition = $this->entityFieldManager
          ->getBaseFieldDefinitions($entity_type_id)[$field_name];

        $field_type = $definition->getType();
        $property = $definition->getMainPropertyName() ?? 'value';

        $count = $this->entityTypeManager()->getStorage('field_suggestion')
          ->getQuery()
          ->condition('entity_type', $entity_type_id)
          ->condition('field_name', $field_name)
          ->condition($this->helper->field($field_type), $field->$property)
          ->range(0, 1)
          ->count()
          ->execute();

        return AccessResult::allowedIf(
          $count > 0 ||
          !in_array(
            $field->$property,
            $this->helper->ignored($entity_type_id, $field_name)
          )
        );
      }
    }

    return AccessResult::neutral();
  }

}
