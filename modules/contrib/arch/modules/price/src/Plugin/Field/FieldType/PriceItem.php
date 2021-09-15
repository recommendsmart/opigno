<?php

namespace Drupal\arch_price\Plugin\Field\FieldType;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

/**
 * Plugin implementation of the 'price' field type.
 *
 * @FieldType(
 *   id = "price",
 *   label = @Translation("Price", context = "arch_price"),
 *   default_widget = "price_default",
 *   default_formatter = "price_default",
 *   list_class = "\Drupal\arch_price\Plugin\Field\FieldType\PriceFieldItemList"
 * )
 */
class PriceItem extends FieldItemBase implements PriceItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['base'] = DataReferenceTargetDefinition::create('string')
      ->setLabel(t('Price calculation base', [], ['context' => 'arch_price']))
      ->addConstraint('Length', ['max' => 5]);

    $properties['price_type'] = DataReferenceTargetDefinition::create('string')
      ->setLabel(t('Price type ID', [], ['context' => 'arch_price']));

    $properties['price_type_entity'] = DataReferenceDefinition::create('entity')
      ->setLabel(t('Price type entity', [], ['context' => 'arch_price']))
      // The entity object is computed out of the entity ID.
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create('price_type'))
      // We can add a constraint for the target entity type. The list of
      // referenceable bundles is a field setting, so the corresponding
      // constraint is added dynamically in ::getConstraints().
      ->addConstraint('EntityType', 'price_type');

    $properties['currency'] = DataDefinition::create('string')
      ->setLabel(t('Currency', [], ['context' => 'arch_price']))
      ->addConstraint('Length', ['max' => 5]);

    $properties['currency_entity'] = DataReferenceDefinition::create('entity')
      ->setLabel(t('Currency entity', [], ['context' => 'arch_price']))
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create('currency'))
      // We can add a constraint for the target entity type. The list of
      // referenceable bundles is a field setting, so the corresponding
      // constraint is added dynamically in ::getConstraints().
      ->addConstraint('EntityType', 'currency');

    $properties['net'] = DataDefinition::create('float')
      ->setLabel(t('Net price', [], ['context' => 'arch_price']))
      ->setSetting('precision', 3)
      ->setSetting('size', 14);

    $properties['gross'] = DataDefinition::create('float')
      ->setLabel(t('Gross price', [], ['context' => 'arch_price']))
      ->setSetting('precision', 3)
      ->setSetting('size', 14);

    $properties['vat_category'] = DataDefinition::create('string')
      ->setLabel(t('VAT category', [], ['context' => 'arch_price']))
      ->addConstraint('Length', ['max' => 32]);

    $properties['vat_category_entity'] = DataReferenceDefinition::create('entity')
      ->setLabel(t('VAT category entity', [], ['context' => 'arch_price']))
      // The entity object is computed out of the entity ID.
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create('vat_category'))
      // We can add a constraint for the target entity type. The list of
      // referenceable bundles is a field setting, so the corresponding
      // constraint is added dynamically in ::getConstraints().
      ->addConstraint('EntityType', 'vat_category');

    $properties['vat_rate'] = DataDefinition::create('float')
      ->setLabel(t('VAT rate', [], ['context' => 'arch_price']))
      ->setSetting('precision', 4)
      ->setSetting('size', 8);

    $properties['vat_value'] = DataDefinition::create('float')
      ->setLabel(t('VAT value', [], ['context' => 'arch_price']))
      ->setSetting('precision', 3)
      ->setSetting('size', 14);

    $properties['date_from'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(t('Date from', [], ['context' => 'arch_price']));
    $properties['date_to'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(t('Date to', [], ['context' => 'arch_price']));

    $properties['available_from'] = DataDefinition::create('any')
      ->setLabel(t('Computed start date', [], ['context' => 'arch_price']))
      ->setDescription(t('The computed start DateTime object.', [], ['context' => 'arch_price']))
      ->setComputed(TRUE)
      ->setClass('\Drupal\datetime\DateTimeComputed')
      ->setSetting('date source', 'date_from');
    $properties['available_to'] = DataDefinition::create('any')
      ->setLabel(t('Computed end date', [], ['context' => 'arch_price']))
      ->setDescription(t('The computed end DateTime object.', [], ['context' => 'arch_price']))
      ->setComputed(TRUE)
      ->setClass('\Drupal\datetime\DateTimeComputed')
      ->setSetting('date source', 'date_to');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $values = parent::toArray();

    $values['net'] = $this->getNetPrice();
    $values['gross'] = $this->getGrossPrice();
    $values['vat_rate'] = $this->getVatRate();
    $values['vat_value'] = $this->getVatValue();

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function toPrice() {
    return $this->getPriceFactory()->getInstance($this->toArray());
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if (parent::isEmpty()) {
      return TRUE;
    }

    if (
      $this->getGrossPrice() === floatval(0)
      && $this->getNetPrice() === floatval(0)
    ) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'price_type' => [
          'type' => 'varchar_ascii',
          'length' => 32,
        ],
        'base' => [
          'type' => 'varchar_ascii',
          'length' => 5,
        ],
        'currency' => [
          'type' => 'varchar_ascii',
          'length' => 5,
        ],
        'net' => [
          'type' => 'numeric',
          'precision' => 14,
          'scale' => 3,
        ],
        'gross' => [
          'type' => 'numeric',
          'precision' => 14,
          'scale' => 3,
        ],
        'vat_category' => [
          'type' => 'varchar_ascii',
          'length' => 32,
        ],
        'vat_rate' => [
          'type' => 'numeric',
          'precision' => 8,
          'scale' => 4,
        ],
        'vat_value' => [
          'type' => 'numeric',
          'precision' => 14,
          'scale' => 3,
        ],
        'date_from' => [
          'description' => 'The date value.',
          'type' => 'varchar',
          'length' => 20,
        ],
        'date_to' => [
          'description' => 'The date value.',
          'type' => 'varchar',
          'length' => 20,
        ],
      ],
      'indexed' => [
        'type' => ['price_type'],
        'currency' => ['currency'],
        'net_price' => ['price_type', 'net'],
        'gross_price' => ['price_type', 'gross'],
        'availability' => ['date_from', 'date_to'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPriceTypeId() {
    return $this->get('price_type')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getPriceType() {
    $price_type_id = $this->getPriceTypeId();
    $storage = $this->getEntityTypeManager()->getStorage('price_type');
    return $storage->load($price_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrencyId() {
    return $this->get('currency')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    $currency_id = $this->getCurrencyId();
    $storage = $this->getEntityTypeManager()->getStorage('currency');
    return $storage->load($currency_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getCalculationBase() {
    return $this->get('base')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getNetPrice() {
    if ($this->getCalculationBase() === 'net') {
      return round((float) $this->get('net')->getValue(), 2);
    }
    $gross = round((float) $this->get('gross')->getValue(), 2);
    $rate = $this->getVatRate();
    return round($gross / (1 + $rate), 2);
  }

  /**
   * {@inheritdoc}
   */
  public function getGrossPrice() {
    if ($this->getCalculationBase() === 'gross') {
      return round((float) $this->get('gross')->getValue(), 2);
    }
    $net = round((float) $this->get('net')->getValue(), 2);
    $rate = $this->getVatRate();
    return round($net * (1 + $rate), 2);
  }

  /**
   * {@inheritdoc}
   */
  public function getVatCategoryId() {
    return $this->get('vat_category')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getVatCategory() {
    $vat_category_id = $this->getVatCategoryId();
    $storage = $this->getEntityTypeManager()->getStorage('vat_category');
    return $storage->load($vat_category_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getVatRate() {
    $vat_category = $this->getVatCategory();
    if ($vat_category->isCustom()) {
      return round((float) $this->get('vat_rate')->getValue(), 4);
    }

    return $vat_category->getRate();
  }

  /**
   * {@inheritdoc}
   */
  public function getVatRatePercentage() {
    return round($this->getVatRate() * 100, 2);
  }

  /**
   * {@inheritdoc}
   */
  public function getVatValue() {
    return round($this->getGrossPrice() - $this->getNetPrice(), 2);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableFrom() {
    /** @var \Drupal\Core\TypedData\Plugin\DataType\DateTimeIso8601 $date */
    $date = $this->get('date_from');
    return $date->getDateTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableTo() {
    /** @var \Drupal\Core\TypedData\Plugin\DataType\DateTimeIso8601 $date */
    $date = $this->get('date_to');
    return $date->getDateTime();
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    $timestamp = $this->getTime()->getCurrentTime();
    $time = DateTimePlus::createFromTimestamp($timestamp);
    return $this->isAvailableAt($time);
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailableAt($time) {
    $from = $this->getAvailableFrom();
    $to = $this->getAvailableTo();
    $time = $time->getTimestamp();

    if (!isset($from) && !isset($to)) {
      return TRUE;
    }

    if (isset($from) && $time < $from->getTimestamp()) {
      return FALSE;
    }

    if (isset($to) && $time > $to->getTimestamp()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Get time service.
   *
   * @return \Drupal\Component\Datetime\TimeInterface
   *   Time service.
   */
  protected function getTime() {
    // @codingStandardsIgnoreStart
    return \Drupal::time();
    // @codingStandardsIgnoreEnd
  }

  /**
   * Entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   Entity type manager.
   */
  protected function getEntityTypeManager() {
    // @codingStandardsIgnoreStart
    return \Drupal::entityTypeManager();
    // @codingStandardsIgnoreEnd
  }

  /**
   * Price factory.
   *
   * @return \Drupal\arch_price\Price\PriceFactoryInterface
   *   Price factory.
   */
  protected function getPriceFactory() {
    // @codingStandardsIgnoreStart
    return \Drupal::service('price_factory');
    // @codingStandardsIgnoreEnd
  }

}
