<?php

namespace Drupal\arch_payment_saferpay\Plugin\PaymentMethod;

use Drupal\arch\ConfigurableArchPluginInterface;
use Drupal\arch_payment\ConfigurablePaymentMethodBase;
use Drupal\arch_price\Price\PriceFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the transfer payment for payment methods plugins.
 *
 * @PaymentMethod(
 *   id = "saferpay",
 *   label = @Translation("Credit card", context = "arch_payment_saferpay"),
 *   administrative_label = @Translation("Saferpay payment", context = "arch_payment_saferpay"),
 *   description = @Translation("Payment method to checkout with Saferpay payment gateway.", context = "arch_payment_saferpay"),
 *   module = "arch_payment_saferpay",
 *   callback_route = "arch_payment_saferpay.redirect",
 * )
 */
class Saferpay extends ConfigurablePaymentMethodBase implements ConfigurableArchPluginInterface, PluginFormInterface {

  const CONFIG_NAME = 'arch_payment_saferpay.settings';

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
   * State.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    KeyValueFactoryInterface $key_value_factory,
    ModuleHandlerInterface $module_handler,
    PluginFormFactoryInterface $plugin_form_factory,
    AccountInterface $current_user,
    PriceFactoryInterface $priceFactory,
    ConfigFactoryInterface $config_factory,
    PathValidatorInterface $path_validator,
    RequestContext $request_context,
    StateInterface $state
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

    $this->configFactory = $config_factory;
    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
    $this->state = $state;
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
      $container->get('config.factory'),
      $container->get('path.validator'),
      $container->get('router.request_context'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get(self::CONFIG_NAME);

    $form['arch_payment_saferpay']['test_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test mode', [], ['context' => 'arch_payment_saferpay']),
      '#default_value' => $this->state->get('arch_payment_saferpay_test', TRUE),
    ];

    $form['arch_payment_saferpay']['terminal_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Terminal id', [], ['context' => 'arch_payment_saferpay']),
      '#default_value' => $config->get('terminal_id'),
      '#required' => TRUE,
    ];

    $form['arch_payment_saferpay']['customer_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer id', [], ['context' => 'arch_payment_saferpay']),
      '#default_value' => $config->get('customer_id'),
      '#required' => TRUE,
    ];

    $form['arch_payment_saferpay']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username', [], ['context' => 'arch_payment_saferpay']),
      '#default_value' => $config->get('username'),
      '#required' => TRUE,
    ];

    $form['arch_payment_saferpay']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password', [], ['context' => 'arch_payment_saferpay']),
      '#default_value' => $config->get('password'),
      '#required' => TRUE,
    ];

    $form['arch_payment_saferpay']['spec_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SpecVersion', [], ['context' => 'arch_payment_saferpay']),
      '#default_value' => $config->get('spec_version'),
      '#required' => TRUE,
    ];
    $form['arch_payment_saferpay']['force_sca'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force SCA', [], ['context' => 'arch_payment_saferpay']),
      '#default_value' => $config->get('force_sca'),
      '#description' => $this->t('For detailed description about PSD2 and SCA see: <a href=":link">:link</a>', [
        ':link' => 'https://saferpay.github.io/sndbx/psd2.html',
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo Implement validateConfigurationForm() method.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable(self::CONFIG_NAME);
    $config->set('customer_id', $form_state->getValue([
      'arch_payment_saferpay',
      'customer_id',
    ]));
    $config->set('terminal_id', $form_state->getValue([
      'arch_payment_saferpay',
      'terminal_id',
    ]));
    $config->set('username', $form_state->getValue([
      'arch_payment_saferpay',
      'username',
    ]));
    $config->set('password', $form_state->getValue([
      'arch_payment_saferpay',
      'password',
    ]));
    $config->set('spec_version', $form_state->getValue([
      'arch_payment_saferpay',
      'spec_version',
    ]));
    $config->set('force_sca', $form_state->getValue([
      'arch_payment_saferpay',
      'force_sca',
    ]));
    $config->save();

    if ($form_state->getValue(['arch_payment_saferpay', 'test_mode'])) {
      $this->state->set('arch_payment_saferpay_test', TRUE);
    }
    else {
      $this->state->set('arch_payment_saferpay_test', FALSE);
    }
  }

}
