<?php

namespace Drupal\arch_price\Price;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\currency\Entity\CurrencyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default price implementation.
 *
 * @package Drupal\arch_price\Price
 */
class Price implements PriceInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * Price values.
   *
   * @var array
   */
  protected $values;

  /**
   * Currency entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * Price type storage.
   *
   * @var \Drupal\arch_price\Entity\Storage\PriceTypeStorageInterface
   */
  protected $priceTypeStorage;

  /**
   * Exchange provider.
   *
   * @var \Drupal\currency\PluginBasedExchangeRateProvider
   */
  protected $exchangeProvider;

  /**
   * Price factory.
   *
   * @var \Drupal\arch_price\Price\PriceFactoryInterface
   */
  protected $priceFactory;

  /**
   * VAT Category storage.
   *
   * @var \Drupal\arch_price\Entity\Storage\VatCategoryStorageInterface
   */
  protected $vatCategoryStorage;

  /**
   * Price constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Service container.
   * @param array|null $values
   *   Price values.
   */
  public function __construct(
    ContainerInterface $container,
    array $values = NULL
  ) {
    $this->currencyStorage = $container->get('entity_type.manager')->getStorage('currency');
    $this->vatCategoryStorage = $container->get('entity_type.manager')->getStorage('vat_category');
    $this->priceTypeStorage = $container->get('entity_type.manager')->getStorage('price_type');
    $this->exchangeProvider = $container->get('currency.exchange_rate_provider');
    $this->priceFactory = $container->get('price_factory');
    if (!empty($values)) {
      $this->setValues($values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $values
  ) {
    return new static(
      $container,
      $values
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValues() {
    return [
      'base' => $this->getCalculationBase(),
      'price_type' => isset($this->values['price_type']) ? $this->values['price_type'] : NULL,
      'currency' => $this->getCurrencyId(),
      'net' => $this->getNetPrice(),
      'gross' => $this->getGrossPrice(),
      'vat_category' => $this->getVatCategoryId(),
      'vat_rate' => $this->getVatRate(),
      'vat_value' => $this->getVatValue(),
      'date_from' => isset($this->values['date_from']) ? $this->values['date_from'] : NULL,
      'date_to' => isset($this->values['date_to']) ? $this->values['date_to'] : NULL,
      'reason_of_diff' => isset($this->values['reason_of_diff']) ? $this->values['reason_of_diff'] : NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setValues(array $values) {
    if (!isset($values['currency'])) {
      throw new \InvalidArgumentException('Value "currency" is missing. Currency is required!');
    }
    if (!isset($values['base'])) {
      throw new \InvalidArgumentException('Value "base" is missing. Calculation base is required!');
    }

    $values += [
      'base' => NULL,
      'price_type' => NULL,
      'currency' => NULL,
      'net' => NULL,
      'gross' => NULL,
      'vat_category' => NULL,
      'vat_rate' => NULL,
      'vat_value' => NULL,
      'date_from' => NULL,
      'date_to' => NULL,
      'reason_of_diff' => NULL,
    ];

    $this->values = $values;
    $currency = $this->currencyStorage->load($values['currency']);
    if (!$currency) {
      $currency = $this->currencyStorage->load('XXX');
    }
    $this->values['currency'] = $currency->id();

    if (
      empty($values['vat_rate'])
      && !empty($values['vat_category'])
      && ($vat_category = $this->vatCategoryStorage->load($values['vat_category']))
    ) {
      /** @var \Drupal\arch_price\Entity\VatCategoryInterface $vat_category */
      $this->values['vat_category'] = $vat_category->id();
      $this->values['vat_rate'] = $vat_category->getRate();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNetPrice() {
    if ($this->getCalculationBase() === 'net') {
      return round((float) $this->values['net'], 2);
    }
    $gross = round((float) $this->values['gross'], 2);
    $rate = $this->getVatRate();
    return round($gross / (1 + $rate), 2);
  }

  /**
   * {@inheritdoc}
   */
  public function getGrossPrice() {
    if ($this->getCalculationBase() === 'gross') {
      return round((float) $this->values['gross'], 2);
    }
    $net = round((float) $this->values['net'], 2);
    $rate = $this->getVatRate();
    return round($net * (1 + $rate), 2);
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
  public function getVatRate() {
    return $this->values['vat_rate'];
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
  public function getCurrencyId() {
    return $this->values['currency'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    if (!empty($this->values['currency'])) {
      return $this->currencyStorage->load($this->values['currency']);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriceType() {
    try {
      return $this->priceTypeStorage->load($this->values['price_type']);
    }
    catch (\Exception $e) {
      // @todo handle error.
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriceTypeId() {
    return $this->values['price_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCalculationBase() {
    return $this->values['base'];
  }

  /**
   * {@inheritdoc}
   */
  public function getVatCategoryId() {
    return $this->values['vat_category'];
  }

  /**
   * {@inheritdoc}
   */
  public function getExchangedPriceValues($currency) {
    if (empty($currency)) {
      throw new \InvalidArgumentException('Missing currency!');
    }

    if (is_string($currency)) {
      $currency = $this->currencyStorage->load($currency);
    }

    if (!($currency instanceof CurrencyInterface)) {
      throw new \InvalidArgumentException('Invalid currency!');
    }

    /** @var \Drupal\currency\Entity\CurrencyInterface $currency */
    /** @var \Drupal\currency\ExchangeRateInterface $rate */
    $rate = $this->exchangeProvider->load($this->values['currency'], $currency->id());
    if (empty($rate)) {
      return NULL;
    }

    $values = $this->getValues();

    $net = $values['net'] * $rate->getRate();
    $gross = $values['gross'] * $rate->getRate();

    $values['currency'] = $currency->id();
    $values['net'] = round($net, 2);
    $values['gross'] = round($gross, 2);
    $values['vat_value'] = round($gross - $net, 2);

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getExchangedPrice($currency) {
    $values = $this->getExchangedPriceValues($currency);
    if (empty($values)) {
      return $values;
    }

    return $this->priceFactory->getInstance($values);
  }

  /**
   * {@inheritdoc}
   */
  public function setReasonOfDifference($reason) {
    $this->values['reason_of_diff'] = (string) $reason;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReasonOfDifference() {
    return $this->values['reason_of_diff'];
  }

}
