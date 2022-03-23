<?php

namespace Drupal\eca\Plugin\DataType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\eca\TypedData\DataTransferObjectDefinition;

/**
 * Defines the "dto" data type.
 *
 * A Data Transfer Object (DTO) allows attachment of arbitrary properties.
 * A DTO can also be used as a list, items may be dynamically added by using '+'
 * and removed by using '-'. Example: $dto->set('+', $value).
 *
 * @DataType(
 *   id = "dto",
 *   label = @Translation("Data Transfer Object"),
 *   description = @Translation("Data Transfer Objects (DTOs) which may contain arbitrary and user-defined properties of data."),
 *   definition_class = "\Drupal\eca\TypedData\DataTransferObjectDefinition"
 * )
 */
class DataTransferObject extends Map {

  /**
   * Creates a new instance of a DTO.
   *
   * @param mixed $value
   *   (optional) The value to set, in conformance to ::setValue().
   * @param \Drupal\Core\TypedData\TypedDataInterface|null $parent
   *   (optional) If known, the parent object.
   * @param string|null $name
   *   (optional) If the parent is given, the property name of the parent.
   * @param bool $notify
   *   (optional) Whether to notify the parent object of the change.
   *
   * @return static
   *   The DTO instance.
   */
  public static function create($value = NULL, ?TypedDataInterface $parent = NULL, ?string $name = NULL, bool $notify = TRUE): DataTransferObject {
    $manager = \Drupal::typedDataManager();
    /** @var \Drupal\eca\Plugin\DataType\DataTransferObject $dto */
    if ($parent && $name) {
      $dto = $manager->createInstance('dto', [
        'data_definition' => DataTransferObjectDefinition::create('dto'),
        'name' => $name,
        'parent' => $parent,
      ]);
    }
    else {
      $dto = $manager->create(DataTransferObjectDefinition::create('dto'));
    }
    if (isset($value)) {
      $dto->setValue($value, $notify);
    }
    return $dto;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    // Make sure that the data definition reflects dynamically added properties.
    $this->definition = DataTransferObjectDefinition::create($definition->getDataType(), $this);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $value = [];
    // Build up an associative array that holds both the data types and the
    // corresponding contained values, so that the property list holding
    // typed data objects may be restored at any subsequent processing.
    foreach ($this->properties as $name => $property) {
      $definition = $property->getDataDefinition();
      if (!$definition->isComputed()) {
        $value['types'][$name] = $definition->getDataType();
        $value['values'][$name] = $property->getValue();
      }
    }
    return $value;
  }

  /**
   * Overrides \Drupal\Core\TypedData\Plugin\DataType\Map::setValue().
   *
   * A DTO allows arbitrary properties. In order to know about the correct data
   * types of given properties, passed values should be typed data objects.
   * Alternatively, scalar values may be passed in directly in case it's also
   * not that critical that a given value may be (wrongly) treated as a string.
   * Otherwise, an additional types key should be provided (see description of
   * the $values argument).
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface[]|null $values
   *   An array of property values as typed data objects, scalars or entities.
   *   Alternatively, if typed data objects are not available at this point, the
   *   values may be an associative array keyed by 'types' and 'values'. Both
   *   array values are a sequence that match with their array keys,
   *   which are in turn property names.
   * @param bool $notify
   *   (optional) Whether to notify the parent object of the change. Defaults to
   *   TRUE. If a property is updated from a parent object, set it to FALSE to
   *   avoid being notified again.
   */
  public function setValue($values, $notify = TRUE) {
    if ($values instanceof TypedDataInterface) {
      $values = $values->getValue();
    }
    if (isset($values) && !is_array($values)) {
      throw new \InvalidArgumentException("Invalid values given. Values must be represented as an associative array.");
    }
    if (empty($values['types']) || empty($values['values'])) {
      foreach ($values as $name => $value) {
        if (!($value instanceof TypedDataInterface)) {
          if ($value instanceof EntityInterface) {
            $values[$name] = $this->wrapEntityValue($name, $value);
          }
          elseif (is_scalar($value)) {
            $values[$name] = $this->wrapScalarValue($name, $value);
          }
          else {
            throw new \InvalidArgumentException("Invalid values given. Values must be of scalar types, entities or typed data objects.");
          }
        }
      }
    }
    else {
      $manager = $this->getTypedDataManager();
      $instances = [];
      foreach ($values['types'] as $name => $type) {
        $instance = $manager->createInstance($type, [
          'data_definition' => $manager->createDataDefinition($type),
          'name' => $name,
          'parent' => $this,
        ]);
        $instance->setValue($values[$name], FALSE);
        $instances[$name] = $instance;
      }
      $values = $instances;
    }
    // Update any existing property objects.
    foreach ($this->properties as $name => $property) {
      if (isset($values[$name])) {
        $property->setValue($values[$name]->getValue(), FALSE);
      }
      else {
        // Property does not exist anymore, thus remove it.
        unset($this->properties[$name]);
      }
      // Remove the value from $this->values to ensure it does not contain any
      // value for computed properties.
      unset($this->values[$name]);
    }
    // Add new properties.
    $this->properties += $values;

    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties($include_computed = FALSE) {
    $properties = [];
    foreach ($this->properties as $name => $property) {
      $definition = $property->getDataDefinition();
      if ($include_computed || !$definition->isComputed()) {
        $properties[$name] = $property;
      }
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function writePropertyValue($property_name, $value) {
    if ($property_name === '-') {
      if ($value === NULL) {
        array_pop($this->properties);
      }
      else {
        foreach ($this->properties as $name => $property) {
          if ($property->getValue() === $value || $property === $value) {
            unset($this->properties[$name]);
            if (is_int($name) || ctype_digit(strval($name))) {
              $this->rekey($name);
            }
          }
        }
      }
    }
    elseif ($value instanceof TypedDataInterface) {
      if (isset($this->properties[$property_name])) {
        $this->properties[$property_name]->setValue($value->getValue());
      }
      elseif ($property_name === '+') {
        $this->properties[] = $value;
      }
      else {
        $this->properties[$property_name] = $value;
        if (is_int($property_name) || ctype_digit(strval($property_name))) {
          $this->rekey($property_name);
        }
      }
    }
    elseif ($value === NULL) {
      // When receiving NULL as unwrapped $value, then handle this just like
      // removing the property from the list.
      unset($this->properties[$property_name]);
      if (is_int($property_name) || ctype_digit(strval($property_name))) {
        $this->rekey($property_name);
      }
    }
    elseif ($value instanceof EntityInterface) {
      $this->writePropertyValue($property_name, $this->wrapEntityValue($property_name, $value));
    }
    elseif (is_scalar($value)) {
      $this->writePropertyValue($property_name, $this->wrapScalarValue($property_name, $value));
    }
    else {
      throw new \InvalidArgumentException("Invalid value given. Value must be of a scalar type, an entity or a typed data object.");
    }
  }

  /**
   * Magic method: Gets a property value.
   *
   * @param string $name
   *   The name of the property to get; e.g., 'title' or 'name'.
   *
   * @return mixed
   *   The property value.
   *
   * @throws \InvalidArgumentException
   *   If a non-existent property is accessed.
   */
  public function __get($name) {
    // There is either a property object or a plain value - possibly for a
    // not-defined property. If we have a plain value, directly return it.
    if (isset($this->properties[$name])) {
      return $this->properties[$name]->getValue();
    }
  }

  /**
   * Magic method: Sets a property value.
   *
   * @param string $name
   *   The name of the property to set; e.g., 'title' or 'name'.
   * @param mixed $value
   *   The value as typed data object to set, or NULL to unset the property.
   *
   * @throws \InvalidArgumentException
   *   If the given argument is not typed data or not NULL.
   */
  public function __set($name, $value) {
    $this->set($name, $value);
  }

  /**
   * Magic method: Determines whether a property is set.
   *
   * @param string $name
   *   The name of the property to get; e.g., 'title' or 'name'.
   *
   * @return bool
   *   Returns TRUE if the property exists and is set, FALSE otherwise.
   */
  public function __isset($name) {
    if (isset($this->properties[$name])) {
      return $this->properties[$name]->getValue() !== NULL;
    }
    return FALSE;
  }

  /**
   * Magic method: Unsets a property.
   *
   * @param string $name
   *   The name of the property to get; e.g., 'title' or 'name'.
   */
  public function __unset($name) {
    if ($this->definition->getPropertyDefinition($name)) {
      $this->set($name, NULL);
    }
    else {
      // Explicitly unset the property in $this->values if a non-defined
      // property is unset, such that its key is removed from $this->values.
      unset($this->values[$name]);
    }
  }

  /**
   * Wraps the scalar value by a Typed Data object.
   *
   * @param string $name
   *   The property name.
   * @param mixed $value
   *   The scalar value.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The Typed Data object.
   */
  protected function wrapScalarValue($name, $value) {
    $manager = $this->getTypedDataManager();
    $scalar_type = 'string';
    if (is_numeric($value)) {
      $scalar_type = is_int($value) || ctype_digit(strval($value)) ? 'integer' : 'float';
    }
    elseif (is_bool($value)) {
      $scalar_type = 'boolean';
    }
    $instance = $manager->createInstance($scalar_type, [
      'data_definition' => $manager->createDataDefinition($scalar_type),
      'name' => $name,
      'parent' => $this,
    ]);
    $instance->setValue($value, FALSE);
    return $instance;
  }

  /**
   * Wraps the entity by a Typed Data object.
   *
   * @param string $name
   *   The property name.
   * @param Drupal\Core\Entity\EntityInterface $value
   *   The entity.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The Typed Data object.
   */
  protected function wrapEntityValue($name, EntityInterface $value) {
    $manager = $this->getTypedDataManager();
    $instance = $manager->createInstance('entity', [
      'data_definition' => EntityDataDefinition::create($value->getEntityTypeId(), $value->bundle()),
      'name' => $name,
      'parent' => $this,
    ]);
    $instance->setValue($value, FALSE);
    return $instance;
  }

  /**
   * Renumbers the items in the property list.
   *
   * @param int $from_index
   *   Optionally, the index at which to start the renumbering, if it is known
   *   that items before that can safely be skipped (for example, when removing
   *   an item at a given index).
   */
  protected function rekey(int $from_index = 0) {
    $assoc = [];
    $sequence = [];
    foreach ($this->properties as $p_name => $p_val) {
      if (is_int($p_name) || ctype_digit(strval($p_name))) {
        $sequence[] = $p_val;
      }
      else {
        $assoc[$p_name] = $p_val;
      }
    }
    $this->properties = array_merge($assoc, $sequence);
    // Each item holds its own index as a "name", it needs to be updated
    // according to the new list indexes.
    for ($i = $from_index; $i < count($sequence); $i++) {
      $this->properties[$i]->setContext($i, $this);
    }
  }

}
