<?php

namespace Drupal\arch_payment_transfer\Plugin\PaymentMethod;

use Drupal\arch_checkout\Controller\CheckoutCompleteInterface;
use Drupal\arch_order\Entity\OrderInterface;
use Drupal\arch_payment\ConfigurablePaymentMethodBase;
use Drupal\arch_price\Price\PriceFactoryInterface;
use Drupal\arch_price\Price\PriceFormatterInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the transfer payment for payment methods plugins.
 *
 * @PaymentMethod(
 *   id = "transfer",
 *   label = @Translation("Transfer Payment", context = "arch_payment_transfer"),
 *   administrative_label = @Translation("Transfer Payment", context = "arch_payment_transfer"),
 *   description = @Translation("An alternate payment method to checkout if user has no credit card.", context = "arch_payment_transfer"),
 *   module = "arch_payment_transfer",
 *   callback_route = "arch_payment_transfer.success",
 * )
 */
class Transfer extends ConfigurablePaymentMethodBase implements CheckoutCompleteInterface, PluginFormInterface {

  const CONFIG_NAME = 'arch_payment_transfer.settings';

  /**
   * The config factory.
   *
   * Subclasses should use the self::config() method, which may be overridden to
   * address specific needs when loading config, rather than this property
   * directly. See \Drupal\Core\Form\ConfigFormBase::config() for an example of
   * this.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * Price factory.
   *
   * @var \Drupal\arch_price\Price\PriceFactoryInterface
   */
  protected $priceFactory;

  /**
   * Price formatter.
   *
   * @var \Drupal\arch_price\Price\PriceFormatterInterface
   */
  protected $priceFormatter;

  /**
   * Currency storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    KeyValueFactoryInterface $key_value_factory,
    ModuleHandlerInterface $module_handler,
    PluginFormFactoryInterface $plugin_form_factory,
    AccountInterface $current_user,
    PriceFactoryInterface $priceFactory,
    TypedConfigManagerInterface $typed_config_manager,
    ConfigFactoryInterface $config_factory,
    PriceFactoryInterface $price_factory,
    PriceFormatterInterface $price_formatter,
    PathValidatorInterface $path_validator,
    RequestContext $request_context,
    ConfigEntityStorageInterface $currency_storage
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $key_value_factory,
      $module_handler,
      $plugin_form_factory,
      $current_user,
      $priceFactory
    );

    $this->typedConfigManager = $typed_config_manager;
    $this->configFactory = $config_factory;
    $this->priceFactory = $price_factory;
    $this->priceFormatter = $price_formatter;
    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
    $this->currencyStorage = $currency_storage;
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
      $container->get('keyvalue'),
      $container->get('module_handler'),
      $container->get('plugin_form.factory'),
      $container->get('current_user'),
      $container->get('price_factory'),
      $container->get('config.typed'),
      $container->get('config.factory'),
      $container->get('price_factory'),
      $container->get('price_formatter'),
      $container->get('path.validator'),
      $container->get('router.request_context'),
      $container->get('entity_type.manager')->getStorage('currency')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutCompleteStatusMessage() {
    return $this->t(
      'Our team starts to assemble your order after receiving your payment.',
      [],
      ['context' => 'arch_payment_transfer']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutCompleteInfo(OrderInterface $order) {
    $build['transfer'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'container-payment-success',
          'transfer-payment-success',
        ],
      ],
      '#weight' => 60,
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'label',
        '#value' => $this->t('Data required for bank transfer', [], ['context' => 'arch_payment_transfer']),
        '#attributes' => [
          'class' => [
            'container-payment-label',
            'transfer-payment-label',
          ],
        ],
      ],
      'rows' => [],
    ];

    $definition = $this->typedConfigManager->getDefinition('arch_payment_transfer.config');
    $config = $this->configFactory->get('arch_payment_transfer.settings');
    $ignore_keys = [
      '_core',
      'langcode',
      'complete_message',
      'currencies_variables',
    ];
    foreach ($definition['mapping'] as $key => $map) {
      if (in_array($key, $ignore_keys)) {
        continue;
      }

      if (!$value = $this->getTransferPaymentSettingValue($config, $order, $key)) {
        continue;
      }

      // @codingStandardsIgnoreStart
      $build['transfer']['rows'][$key] = [
        '#type' => 'container',
        'label' => [
          '#type' => 'html_tag',
          '#tag' => 'label',
          '#value' => $this->t($map['label'], [], ['context' => 'arch_payment_transfer']),
        ],
        'value' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $value,
        ],
      ];
      // @codingStandardsIgnoreEnd
    }

    $price_format = [
      'label' => FALSE,
      'vat_info' => FALSE,
    ];

    $price = [
      'currency' => $order->get('currency')->getString(),
      'base' => 'gross',
      'net' => 0,
      'gross' => $order->get('grandtotal_gross')->getString(),
      'vat_category' => 'custom',
      'vat_rate' => 0,
      'vat_value' => 0,
    ];

    $grandtotal = $this->priceFactory->getInstance($price);
    $build['transfer']['rows']['grand_total'] = [
      '#type' => 'container',
      'label' => [
        '#type' => 'html_tag',
        '#tag' => 'label',
        '#value' => $this->t('Grand Total', [], ['context' => 'arch_payment_transfer']),
      ],
      'value' => $this->priceFormatter->buildGross($grandtotal, $price_format),
    ];

    $complete_message = $config->get('complete_message');
    if (isset($complete_message) && !empty($complete_message)) {
      // @codingStandardsIgnoreStart
      $build['status']['#value'] = $this->t($complete_message, [], ['context' => 'arch_payment_transfer']);
      // @codingStandardsIgnoreEnd
    }

    return $build['transfer'];
  }

  /**
   * Get transfer payment setting value to render.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Config.
   * @param \Drupal\arch_order\Entity\OrderInterface $order
   *   Order entity.
   * @param string|array $key
   *   A string that maps to a key within the configuration data.
   *
   * @return mixed
   *   Setting value.
   */
  protected function getTransferPaymentSettingValue(ImmutableConfig $config, OrderInterface $order, $key) {
    if ($key == 'announcement') {
      $announcement = $order->get('order_number')->getString();
      return $announcement;
    }
    $currency = $order->get('currency')->getString();

    $currencies_variables = $config->get('currencies_variables');
    if (!empty($currencies_variables[$currency][$key])) {
      return $currencies_variables[$currency][$key];
    }

    return $config->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get(self::CONFIG_NAME);

    $form['arch_payment_transfer']['complete_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom Checkout Complete Message', [], ['context' => 'arch_payment_transfer']),
      '#default_value' => $config->get('complete_message'),
      '#required' => FALSE,
    ];

    $currencies = $this->getCurrenciesList();

    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#parents' => [
        'settings',
      ],
    ];

    foreach ($currencies as $currency) {
      $form['currencies_variables'][$currency] = [
        '#type' => 'details',
        '#title' => $currency,
        '#group' => 'settings',
      ];

      $items_info = $this->getElementsInfo();
      foreach ($items_info as $item_key => $item_info) {
        $form['currencies_variables'][$currency][$item_key] = [
          '#type' => 'textfield',
          '#title' => $item_info['title'],
          '#default_value' => $this->getElementDefaultValue($currency, $item_key, $config),
          '#required' => $this->getElementRequired($currency, $item_key),
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable(self::CONFIG_NAME);

    // Set default values.
    $config->set('complete_message', $form_state->getValue([
      'arch_payment_transfer',
      'complete_message',
    ]));

    $currencies = $this->getCurrenciesList();
    $items_info = $this->getElementsInfo();
    $currencies_variables = [];
    foreach ($currencies as $currency) {
      foreach (array_keys($items_info) as $item_key) {
        $currencies_variables[$currency][$item_key] = $form_state->getValue([
          'currencies_variables',
          $currency,
          $item_key,
        ]);
      }
    }

    // Save currencies variables.
    $config->set('currencies_variables', $currencies_variables);

    // Save changes.
    $config->save();
  }

  /**
   * Get form elements basic info.
   *
   * @return array
   *   Info array.
   */
  protected function getElementsInfo() {
    return [
      'business_name' => [
        'title' => $this->t('Business Name', [], ['context' => 'arch_payment_transfer']),
        'required' => TRUE,
      ],
      'account_number' => [
        'title' => $this->t('Bank Account Number', [], ['context' => 'arch_payment_transfer']),
        'required' => TRUE,
      ],
      'bank_provider' => [
        'title' => $this->t('Bank Provider', [], ['context' => 'arch_payment_transfer']),
        'required' => TRUE,
      ],
      'announcement' => [
        'title' => $this->t('Announcement', [], ['context' => 'arch_payment_transfer']),
      ],
      'customer_bic' => [
        'title' => $this->t('BIC Number', [], ['context' => 'arch_payment_transfer']),
      ],
      'customer_iban' => [
        'title' => $this->t('IBAN Number', [], ['context' => 'arch_payment_transfer']),
      ],
    ];
  }

  /**
   * Get form element key.
   *
   * @param string $currency
   *   Currency ID.
   * @param string $item_key
   *   Config key.
   *
   * @return string
   *   Element key.
   */
  protected function getElementKey($currency, $item_key) {
    if ('default' == $currency) {
      return $item_key;
    }
    return $currency . '_' . $item_key;
  }

  /**
   * Get form element required value.
   *
   * @param string $currency
   *   Currency ID.
   * @param string $item_key
   *   Config key.
   *
   * @return bool
   *   Return TRUE if element required.
   */
  protected function getElementRequired($currency, $item_key) {
    if ($currency != 'default') {
      return FALSE;
    }
    $items_info = $this->getElementsInfo();
    if (empty($items_info[$item_key]['required'])) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get form element Default value.
   *
   * @param string $currency
   *   Currency ID.
   * @param string $item_key
   *   Config key.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Config.
   *
   * @return null|string
   *   Default value.
   */
  protected function getElementDefaultValue($currency, $item_key, ImmutableConfig $config) {
    $currencies_variables = $config->get('currencies_variables');
    if (empty($currencies_variables[$currency][$item_key])) {
      return NULL;
    }
    return $currencies_variables[$currency][$item_key];
  }

  /**
   * Get currencies list.
   *
   * @return array
   *   List of currency IDs.
   */
  protected function getCurrenciesList() {
    $currencies = ['default'];
    foreach ($this->currencyStorage->loadMultiple() as $currency) {
      if ($currency->id() == 'XXX') {
        continue;
      }
      $currencies[] = $currency->id();
    }
    return $currencies;
  }

}
