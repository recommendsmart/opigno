<?php

namespace Drupal\arch_product\Entity;

/**
 * Product availability checker.
 *
 * @package Drupal\arch_product\Entity
 */
class ProductAvailability implements ProductAvailabilityInterface {

  /**
   * Get availability options.
   *
   * @return array
   *   List of avalibility values with labels.
   */
  public static function getOptions() {
    $options = [
      ProductAvailabilityInterface::STATUS_AVAILABLE => t('Available', [], ['context' => 'arch_product_availability']),
      ProductAvailabilityInterface::STATUS_NOT_AVAILABLE => t('Not available', [], ['context' => 'arch_product_availability']),
      ProductAvailabilityInterface::STATUS_PREORDER => t('Preorder', [], ['context' => 'arch_product_availability']),
    ];

    \Drupal::moduleHandler()->alter('arch_product_availability_options', $options);

    return $options;
  }

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The human readable name.
   *
   * @var string
   */
  protected $name;

  /**
   * Constructs a new class instance.
   *
   * @param array $values
   *   An array of property values, keyed by property name, used to construct
   *   the language.
   */
  public function __construct(array $values = []) {
    // Set all the provided properties for the language.
    foreach ($values as $key => $value) {
      if (property_exists($this, $key)) {
        $this->{$key} = $value;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    if ($this->id && !$this->name) {
      $this->name = static::getOptions()[$this->id] ?: NULL;
    }
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

}
