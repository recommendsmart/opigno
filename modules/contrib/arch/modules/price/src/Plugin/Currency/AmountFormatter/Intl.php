<?php

namespace Drupal\arch_price\Plugin\Currency\AmountFormatter;

use Commercie\Currency\CurrencyInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\currency\LocaleResolverInterface;
use Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formats amounts using PHP's Intl extension.
 *
 * @CurrencyAmountFormatter(
 *   description = @Translation("Formats amounts using locales' Unicode number patterns for increased accuracy."),
 *   id = "arch_price_currency_intl",
 *   label = @Translation("Fixed Intl formatter")
 * )
 */
class Intl extends PluginBase implements AmountFormatterInterface, ContainerFactoryPluginInterface {

  /**
   * The locale delegator.
   *
   * @var \Drupal\currency\LocaleResolver
   */
  protected $localeDelegator;

  /**
   * Constructs a new instance.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed[] $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\currency\LocaleResolverInterface $locale_delegator
   *   The locale delegator.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    LocaleResolverInterface $locale_delegator
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $this->localeDelegator = $locale_delegator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('currency.locale_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formatAmount(CurrencyInterface $currency, $amount, $language_type = LanguageInterface::TYPE_CONTENT) {
    $currency_locale = $this->localeDelegator->resolveCurrencyLocale();
    $decimal_position = strpos($amount, '.');
    $number_of_decimals = $decimal_position !== FALSE ? strlen(substr($amount, $decimal_position + 1)) : 0;
    $is_zero = floatval($amount) === floatval(0);

    $formatter = new \NumberFormatter(
      $currency_locale->getLocale(),
      \NumberFormatter::CURRENCY,
      $currency_locale->getPattern()
    );
    $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $number_of_decimals);
    $formatter->setAttribute(\NumberFormatter::DECIMAL_ALWAYS_SHOWN, FALSE);
    $formatter->setPattern($currency_locale->getPattern());
    if ($number_of_decimals > 0 && !$is_zero) {
      $formatter->setSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, $currency_locale->getDecimalSeparator());
      $formatter->setSymbol(\NumberFormatter::MONETARY_SEPARATOR_SYMBOL, $currency_locale->getDecimalSeparator());
      $formatter->setSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL, $currency_locale->getGroupingSeparator());
      $formatter->setSymbol(\NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL, $currency_locale->getGroupingSeparator());
    }
    $formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, $currency->getSign());
    $formatter->setSymbol(\NumberFormatter::INTL_CURRENCY_SYMBOL, $currency->getCurrencyCode());

    return $formatter->format($amount);
  }

}
