<?php

namespace Drupal\arch_price\Plugin\search_api\processor;

use Drupal\arch_price\Entity\PriceTypeInterface;
use Drupal\arch_product\Entity\ProductInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Plugin\search_api\data_type\value\TextValue;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds the item's URL to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "search_api_arch_price_value",
 *   label = @Translation("Price value"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class Price extends ProcessorPluginBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Price type storage.
   *
   * @var \Drupal\arch_price\Entity\Storage\PriceTypeStorageInterface
   */
  protected $priceTypeStorage;

  /**
   * Currency storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * Currency list.
   *
   * @var \Drupal\currency\Entity\Currency[]
   */
  protected $currencyList;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->setEntityTypeManager($container->get('entity_type.manager'));
    return $processor;
  }

  /**
   * Sets entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->priceTypeStorage = $entity_type_manager->getStorage('price_type');
    $this->currencyStorage = $entity_type_manager->getStorage('currency');
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if ($datasource) {
      /** @var \Drupal\Core\Entity\ContentEntityType $entity_type */
      $entity_type = $this->entityTypeManager->getDefinition($datasource->getEntityTypeId());
      if ($entity_type->entityClassImplements(ProductInterface::class)) {
        foreach ($this->getDefinitions($datasource) as $property => $definition) {
          $properties[$property] = new ProcessorProperty($definition);
        }
      }
    }

    return $properties;
  }

  /**
   * Get property definition arrays.
   *
   * @param \Drupal\search_api\Datasource\DatasourceInterface $datasource
   *   The datasource this set of properties belongs to.
   *
   * @return array
   *   Property definition array.
   */
  protected function getDefinitions(DatasourceInterface $datasource) {
    $properties = [];
    foreach ($this->priceTypeStorage->loadMultiple() as $price_type) {
      /** @var \Drupal\arch_price\Entity\PriceTypeInterface $price_type */
      $properties += $this->priceTypePropertyDefinitions($price_type, $datasource);
    }
    $properties['arch_price_value'] = [
      'label' => $this->t('Price value', [], ['context' => 'arch_price']),
      'type' => 'string',
      'computed' => TRUE,
      'processor_id' => $this->getPluginId(),
      'datasource' => $datasource,
      'is_list' => TRUE,
      'fields' => '_custom_serialize',
    ];

    return $properties;
  }

  /**
   * Get property definition arrays for given price type.
   *
   * @param \Drupal\arch_price\Entity\PriceTypeInterface $price_type
   *   Price type.
   * @param \Drupal\search_api\Datasource\DatasourceInterface $datasource
   *   The datasource this set of properties belongs to.
   *
   * @return array
   *   Property definitions.
   */
  protected function priceTypePropertyDefinitions(PriceTypeInterface $price_type, DatasourceInterface $datasource) {
    $definition = [
      'computed' => TRUE,
      'processor_id' => $this->getPluginId(),
      'datasource' => $datasource,
      'is_list' => TRUE,
    ];

    $definitions = [];
    $definitions['arch_price_net_' . $price_type->id()] = [
      'label' => $this->t('Price: @price_type net value', ['@price_type' => $price_type->label()], ['context' => 'arch_price']),
      'type' => 'decimal',
      'fields' => ['net'],
    ] + $definition;
    $definitions['arch_price_gross_' . $price_type->id()] = [
      'label' => $this->t('Price: @price_type gross value', ['@price_type' => $price_type->label()], ['context' => 'arch_price']),
      'type' => 'decimal',
      'fields' => ['gross'],
    ] + $definition;

    foreach ($this->getCurrencyList() as $currency) {
      foreach (['net', 'gross'] as $field) {
        $label = $this->t('Price: @price_type @currency net value', [
          '@price_type' => $price_type->label(),
          '@currency' => $currency->id(),
        ], ['context' => 'arch_price']);
        $property = strtolower('arch_price_' . $field . '_' . $price_type->id() . '_' . $currency->id());
        $definitions[$property] = [
          'label' => $label,
          'type' => 'decimal',
          'price_type' => $price_type->id(),
          'currency' => $currency->id(),
          'fields' => [$field],
        ] + $definition;
      }
    }
    return $definitions;
  }

  /**
   * Get currency list.
   *
   * @return \Drupal\currency\Entity\Currency[]
   *   Currenct list.
   */
  protected function getCurrencyList() {
    if (!isset($this->currencyList)) {
      $this->currencyList = $this->currencyStorage->loadMultiple();
    }
    return $this->currencyList;
  }

  /**
   * Get price type properties.
   *
   * @param \Drupal\arch_price\Entity\PriceTypeInterface $price_type
   *   Price type.
   * @param \Drupal\search_api\Datasource\DatasourceInterface $datasource
   *   The datasource this set of properties belongs to.
   *
   * @return \Drupal\search_api\Processor\ProcessorPropertyInterface[]
   *   Property list.
   */
  protected function priceTypeProperties(PriceTypeInterface $price_type, DatasourceInterface $datasource) {
    $properties = [];
    foreach ($this->priceTypePropertyDefinitions($price_type, $datasource) as $property => $definition) {
      unset($definition['fields']);
      $properties[$property] = new ProcessorProperty($definition);
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    /** @var \Drupal\arch_product\Entity\ProductInterface $product */
    $product = $item->getOriginalObject()->getValue();
    foreach ($product->getPrices() as $price) {
      /** @var \Drupal\arch_price\Plugin\Field\FieldType\PriceItemInterface $price */
      foreach ($this->getDefinitions($item->getDatasource()) as $property => $definition) {
        /** @var \Drupal\search_api\Item\Field[] $fields */
        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($item->getFields(), $item->getDatasourceId(), $property);
        if (empty($fields)) {
          continue;
        }

        $value = $this->getValue($price->toArray(), $definition);
        if (empty($value)) {
          continue;
        }

        foreach ($fields as $field) {
          $field->addValue($value);
        }
      }
    }
  }

  /**
   * Get value to store.
   *
   * @param array $price_value
   *   Price value array.
   * @param array $definition
   *   Property definition.
   *
   * @return mixed
   *   Value to store.
   */
  protected function getValue(array $price_value, array $definition) {
    if (empty($definition['fields'])) {
      return NULL;
    }
    if (
      !empty($definition['currency'])
      && $price_value['currency'] != $definition['currency']
    ) {
      return NULL;
    }

    if (
      !empty($definition['price_type'])
      && $price_value['price_type'] != $definition['price_type']
    ) {
      return NULL;
    }

    if ($definition['fields'] === '_custom_serialize') {
      foreach (['date_from', 'date_to'] as $key) {
        if (!isset($price_value[$key])) {
          unset($price_value[$key]);
        }
      }
      ksort($price_value);
      $values = [];
      foreach ($price_value as $key => $value) {
        $values[] = "{$key}:{$value}";
      }
      return new TextValue('||' . implode('||', $values) . '||');
    }

    if (is_array($definition['fields'])) {
      $value = [];
      foreach ($definition['fields'] as $field) {
        $value[] = $price_value[$field];
      }

      if ($definition['type'] === 'string') {
        return new TextValue(implode('', $value));
      }
      if (count($value) === 1) {
        return $value[0];
      }

      return $value;
    }

    return NULL;
  }

}
