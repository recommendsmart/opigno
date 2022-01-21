<?php

namespace Drupal\designs_entity\Plugin\designs\source;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\designs\DesignSourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The design source for entity view modes.
 *
 * @DesignSource(
 *   id = "entity",
 *   label = @Translation("Entity"),
 *   usesRegionsForm = FALSE
 * )
 */
class EntitySource extends DesignSourceBase implements ContainerFactoryPluginInterface {

  /**
   * The entity display.
   *
   * @var \Drupal\Core\Entity\Display\EntityDisplayInterface
   */
  protected $owner;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entityFieldManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSources() {
    $type_id = $this->configuration['type'] ?? '';
    $bundle = $this->configuration['bundle'] ?? $type_id;

    // Generate the fields that will be displayed for the entity field source.
    $fields = [];
    foreach ($this->entityFieldManager->getFieldDefinitions($type_id, $bundle) as $name => $definition) {
      $fields[$name] = $definition->getLabel();
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts(array &$element) {
    // Provide entity as context.
    $contexts = [];
    $entity_type = $this->configuration['type'];
    if (isset($element["#{$entity_type}"])) {
      $contexts[$entity_type] = $element["#{$entity_type}"];
    }
    return $contexts + parent::getContexts($element);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormContexts() {
    $entity_type = $this->configuration['type'];
    return parent::getFormContexts() + [
      $entity_type => EntityContextDefinition::fromEntityTypeId($entity_type),
    ];
  }

}
