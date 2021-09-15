<?php

namespace Drupal\arch_product\Plugin\Field\FieldType;

use Drupal\arch_product\Entity\ProductAvailability;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;

/**
 * Defines the 'product_availability' entity field item.
 *
 * @FieldType(
 *   id = "product_availability",
 *   label = @Translation("Availability", context = "arch_product_availability"),
 *   default_widget = "product_availability_select",
 *   default_formatter = "product_availability",
 *   no_ui = TRUE,
 *   constraints = {
 *     "ComplexData" = {
 *       "value" = {
 *         "Length" = {"max" = 15}
 *       }
 *     }
 *   }
 * )
 */
class ProductAvailabilityItem extends FieldItemBase implements OptionsProviderInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Product availability code', [], ['context' => 'arch_product_availability']))
      ->setRequired(TRUE);

    $properties['product_availability'] = DataReferenceDefinition::create('product_availability')
      ->setLabel(t('Product availability object', [], ['context' => 'arch_product_availability']))
      // The product_availability object is retrieved via the
      // product_availability code.
      ->setComputed(TRUE)
      ->setReadOnly(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar_ascii',
          'length' => 15,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the product_availability property,
    // if no array is given as this handles product_availability codes and
    // objects.
    if (isset($values) && !is_array($values)) {
      $this->set('product_availability', $values, $notify);
    }
    else {
      // Make sure that the 'product_availability' property gets set as 'value'.
      if (isset($values['value']) && !isset($values['product_availability'])) {
        $values['product_availability'] = $values['value'];
      }
      parent::setValue($values, $notify);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Default to the site's default product_availability. When
    // product_availability module is enabled, this behavior is configurable,
    // see arch_product_field_info_alter().
    $this->setValue(['product_availability' => 'available'], $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Make sure that the value and the product_availability property stay in
    // sync.
    if ($property_name == 'value') {
      $this->writePropertyValue('product_availability', $this->value);
    }
    elseif ($property_name == 'product_availability') {
      $this->writePropertyValue('value', $this->get('product_availability')->getTargetIdentifier());
    }
    parent::onChange($property_name, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // Defer to the callback in the item definition as it can be overridden.
    $statuses = array_keys(ProductAvailability::getOptions());
    $values['value'] = $statuses[array_rand($statuses)];
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return array_keys(ProductAvailability::getOptions());
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return ProductAvailability::getOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    return $this->getPossibleValues($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    return $this->getPossibleValues($account);
  }

}
