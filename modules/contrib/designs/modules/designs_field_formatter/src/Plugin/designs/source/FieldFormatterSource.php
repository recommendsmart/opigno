<?php

namespace Drupal\designs_field_formatter\Plugin\designs\source;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\designs\DesignSourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The source using field formatter field.
 *
 * @DesignSource(
 *   id = "field_formatter",
 *   label = @Translation("Field formatter")
 * )
 */
class FieldFormatterSource extends DesignSourceBase implements ContainerFactoryPluginInterface {

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
   * The field type manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entityFieldManager, FieldTypePluginManagerInterface $fieldTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entityFieldManager;
    $this->fieldTypeManager = $fieldTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * Get the field type.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The typed data definition.
   */
  protected function getType() {
    $definitions = $this->entityFieldManager->getFieldDefinitions(
      $this->configuration['type'],
      $this->configuration['bundle'],
    );
    $definition = $definitions[$this->configuration['field']];

    // Get the type based on the field definition.
    return $this->fieldTypeManager->createInstance(
      $definition->getType(),
      [
        'name' => NULL,
        'parent' => NULL,
        'field_definition' => $definition,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSources() {
    $sources = [];

    // Process the data definition for a field.
    $definition = $this->getType()->getDataDefinition();
    foreach ($definition->getPropertyDefinitions() as $prop_id => $property) {
      $sources[$prop_id] = $property->getLabel();
    }

    return $sources;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSources(array $sources, array $element) {
    $output = [];

    $item = $element['#item'];

    // Cycle through all the properties for the field type.
    $definition = $this->getType()->getDataDefinition();
    foreach ($definition->getPropertyDefinitions() as $prop_id => $property) {
      $output[$prop_id] = $this->getMarkup($item->get($prop_id)->getValue());
    }

    return $output;
  }

  /**
   * Get the markup from the value.
   *
   * @param mixed $value
   *   A property value.
   *
   * @return array
   *   The render array.
   */
  protected function getMarkup($value) {
    if (is_null($value)) {
      return ['#markup' => ''];
    }
    elseif (is_scalar($value)) {
      return [
        '#markup' => $value,
      ];
    }
    elseif (method_exists($value, 'toString')) {
      return [
        '#markup' => $value->toString(),
      ];
    }
    elseif (method_exists($value, '__toString')) {
      return [
        '#markup' => (string) $value,
      ];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts(array &$element) {
    return [
      $element['#entity_type'] => $element['#entity'],
    ];
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

  /**
   * {@inheritdoc}
   */
  public function getDefaultSources() {
    $definition = $this->getType()->getDataDefinition();
    if ($definition instanceof ComplexDataDefinitionInterface) {
      $main = $definition->getMainPropertyName();
      if ($main) {
        return [$main];
      }
    }

    $definitions = $definition->getPropertyDefinitions();
    if (isset($definitions['value'])) {
      return ['value'];
    }

    return [key($definitions)];
  }

}
