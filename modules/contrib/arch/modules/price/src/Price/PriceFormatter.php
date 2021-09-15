<?php

namespace Drupal\arch_price\Price;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\currency\Entity\CurrencyInterface;
use Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterInterface;
use Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Price formatter service.
 *
 * @package Drupal\arch_price\Price
 */
class PriceFormatter implements PriceFormatterInterface, ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Amount formatter.
   *
   * @var \Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterInterface
   */
  protected $amountFormatter;

  /**
   * Currency entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * PriceFormatter constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterManagerInterface $amount_formatter_manager
   *   Amount formatter manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AmountFormatterManagerInterface $amount_formatter_manager,
    ModuleHandlerInterface $module_handler,
    RendererInterface $renderer
  ) {
    $this->currencyStorage = $entity_type_manager->getStorage('currency');
    $this->amountFormatter = $amount_formatter_manager->createInstance('arch_price_currency_intl');
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.currency.amount_formatter'),
      $container->get('module_handler'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setAmountFormatter(AmountFormatterInterface $amount_formatter) {
    $this->amountFormatter = $amount_formatter;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFormatted(PriceInterface $price, $mode, array $settings = []) {
    if ($mode == self::FORMAT_FULL) {
      return $this->buildFull($price);
    }

    $price_values = $price->getValues();
    $currency = $price->getCurrency();

    $this->moduleHandler->alter('arch_price_display_settings', $settings, $price_values, $currency);

    $settings += ['wrapper_element' => 'div'];

    $values = [
      self::FORMAT_NET => $price_values['net'],
      self::FORMAT_GROSS => $price_values['gross'],
      self::FORMAT_VAT_VALUE => $price->getVatValue(),
    ];

    $label = NULL;
    if ($mode === self::FORMAT_NET) {
      $label = $this->t('Net', [], ['context' => 'arch_price']);
    }
    elseif ($mode === self::FORMAT_GROSS) {
      $label = $this->t('Gross', [], ['context' => 'arch_price']);
    }
    elseif ($mode === self::FORMAT_VAT_VALUE) {
      $label = $this->t('VAT', [], ['context' => 'arch_price']);
      $settings['vat_info'] = FALSE;
    }

    if (
      !empty($settings['display_currency'])
      && $settings['display_currency'] != $currency->id()
    ) {
      /** @var \Drupal\currency\Entity\CurrencyInterface $display_currency */
      $display_currency = $this->currencyStorage->load($settings['display_currency']);
      if (
        !empty($display_currency)
        && $exchanged_values = $price->getExchangedPriceValues($display_currency)
      ) {
        $price_values = $exchanged_values;
        $currency = $display_currency;
        $values = [
          self::FORMAT_NET => $price_values['net'],
          self::FORMAT_GROSS => $price_values['gross'],
          self::FORMAT_VAT_VALUE => $price_values['vat_value'],
        ];
      }
    }

    $build = [
      '#theme' => 'price',
      '#mode' => $mode,
      '#values' => $price_values,
      '#currency' => $currency,
      '#settings' => $settings,
      '#label' => $label,
      '#label_display' => isset($settings['label']) ? $settings['label'] : TRUE,
      '#vat_rate' => $price->getVatRate(),
      '#vat_info_display' => isset($settings['vat_info']) ? $settings['vat_info'] : TRUE,
      '#formatted' => $this->amountFormatter->formatAmount(
        $currency,
        static::formatAmount($values[$mode], $currency)
      ),
    ];

    return $build;
  }

  /**
   * Apply rounding step currency settings for displayed value.
   *
   * @param float $amount
   *   Displayed amount value.
   * @param \Drupal\currency\Entity\CurrencyInterface $currency
   *   Currency entity.
   *
   * @return string|null
   *   Formatted amount.
   */
  protected static function formatAmount($amount, CurrencyInterface $currency) {
    $amount = bcdiv($amount, $currency->getRoundingStep(), 6);
    $amount = bcmul(round($amount), $currency->getRoundingStep(), 6);

    $decimal_mark_position = strpos($amount, '.');
    // The amount has no decimals yet, so add a decimal mark.
    if ($decimal_mark_position === FALSE) {
      $amount .= '.';
    }
    // Remove any existing trailing zeroes.
    $amount = rtrim($amount, '0');
    // Add the required number of trailing zeroes.
    $amount_decimals = strlen(substr($amount, $decimal_mark_position + 1));
    if ($amount_decimals < $currency->getDecimals()) {
      $amount .= str_repeat('0', $currency->getDecimals() - $amount_decimals);
    }

    return $amount;
  }

  /**
   * {@inheritdoc}
   */
  public function format(PriceInterface $price, $mode, array $settings = []) {
    $build = $this->buildFormatted($price, $mode, $settings);
    return $this->renderer->render($build);
  }

  /**
   * {@inheritdoc}
   */
  public function buildNet(PriceInterface $price, array $settings = []) {
    return $this->buildFormatted($price, self::FORMAT_NET, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function formatNet(PriceInterface $price, array $settings = []) {
    return $this->format($price, self::FORMAT_NET, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function buildGross(PriceInterface $price, array $settings = []) {
    return $this->buildFormatted($price, self::FORMAT_GROSS, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function formatGross(PriceInterface $price, array $settings = []) {
    return $this->format($price, self::FORMAT_GROSS, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFull(PriceInterface $price, array $settings = []) {
    return [
      // @todo Consider adding VAT value.
      self::FORMAT_NET => $this->buildFormatted($price, self::FORMAT_NET, $settings),
      self::FORMAT_GROSS => $this->buildFormatted($price, self::FORMAT_GROSS, $settings),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatFull(PriceInterface $price, array $settings = []) {
    $build = $this->buildFull($price, $settings);
    return $this->renderer->render($build);
  }

  /**
   * {@inheritdoc}
   */
  public function buildVat(PriceInterface $price, array $settings = []) {
    return $this->buildFormatted($price, self::FORMAT_VAT_VALUE, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function formatVat(PriceInterface $price, array $settings = []) {
    return $this->format($price, self::FORMAT_VAT_VALUE, $settings);
  }

}
