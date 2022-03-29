<?php

namespace Drupal\field_suggestion;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of field suggestion entities.
 *
 * @see \Drupal\field_suggestion\Entity\FieldSuggestion
 */
class FieldSuggestionListBuilder extends EntityListBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type
  ) {
    $instance = parent::createInstance($container, $entity_type);

    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityFieldManager = $container->get('entity_field.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'entity_type' => $this->t('Entity type'),
      'field_name' => $this->t('Field name'),
      'field_value' => $this->t('Field value'),
      'usage' => $this->t('Usage'),
      'exclude' => $this->t('Exclude'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];

    $entity_type_id = $entity->entity_type->value;

    $row['entity_type'] = sprintf(
      '%s (%s)',
      $this->entityTypeManager->getDefinition($entity_type_id)->getLabel(),
      $entity_type_id
    );

    $definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);

    $row['field_name'] = sprintf(
      '%s (%s)',
      $definitions[$field_name = $entity->field_name->value]->getLabel(),
      $field_name
    );

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $row['field_value'] = [
      'data' => $this->entityTypeManager->getViewBuilder($entity_type_id)
        ->viewFieldItem(
          $entity->get('field_suggestion_' . $entity->bundle())->first()
        ),
    ];

    /** @var \Drupal\field_suggestion\FieldSuggestionInterface $entity */
    $row['usage'] = $entity->isOnce() ? 1 : '∞';

    $row['exclude'] = $entity->countExcluded();

    return $row + parent::buildRow($entity);
  }

}
