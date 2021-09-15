<?php

namespace Drupal\arch_checkout\Form;

use Drupal\arch_checkout\CheckoutType\CheckoutTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure checkout settings.
 */
class CheckoutSettingsForm extends ConfigFormBase {

  /**
   * Name of the checkout settings config.
   *
   * @var string
   */
  const CONFIG_NAME = 'arch_checkout.settings';

  /**
   * Checkout Type manager.
   *
   * @var \Drupal\arch_checkout\CheckoutType\CheckoutTypeManagerInterface
   */
  protected $checkoutTypeManager;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\arch_checkout\CheckoutType\CheckoutTypeManagerInterface $checkoutTypeManager
   *   The checkout type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CheckoutTypeManagerInterface $checkoutTypeManager) {
    parent::__construct($config_factory);

    $this->checkoutTypeManager = $checkoutTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.checkout_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'checkout_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Plugin selection.
    $form['plugin_id'] = [
      '#type' => 'radios',
      '#title' => $this->t('Checkout type', [], ['context' => 'arch_checkout']),
      '#description' => $this->t('Choose a checkout type for the UI.', [], ['context' => 'arch_checkout']),
      '#default_value' => $this->getDefaultPluginId(),
      '#options' => [],
      '#weight' => -10,
      '#process' => [
        [Radios::class, 'processRadios'],
        [$this, 'processPluginOptions'],
      ],
    ];

    // Plugin setting forms.
    foreach ($this->checkoutTypeManager->getDefinitions() as $plugin_id => $plugin_definition) {
      if ($plugin_id === 'broken') {
        continue;
      }

      $form['plugin_id']['#options'][$plugin_id] = $plugin_definition['label'];
      /** @var \Drupal\arch_checkout\CheckoutType\CheckoutTypePluginInterface $plugin */
      $plugin = $this->checkoutTypeManager->createInstance($plugin_id, []);
      $form = $plugin->buildConfigurationForm($form, $form_state);
    }

    // Additional settings.
    $form['anonymous_checkout'] = [
      '#type' => 'select',
      '#title' => $this->t('Anonymous checkout', [], ['context' => 'arch_checkout']),
      '#options' => [
        'not_allowed' => $this->t('Not allowed', [], ['context' => 'arch_checkout_anonymous']),
        'allow' => $this->t('Allow', [], ['context' => 'arch_checkout_anonymous']),
      ],
      '#default_value' => $this->config(self::CONFIG_NAME)->get('anonymous_checkout'),
      '#weight' => -9,
    ];
    $form['redirect_to_cart_if_empty'] = [
      '#type' => 'select',
      '#title' => $this->t('If no item in Cart', [], ['context' => 'arch_checkout']),
      '#default_value' => $this->config(self::CONFIG_NAME)->get('redirect_to_cart_if_empty'),
      '#options' => [
        '_none' => $this->t('Do nothing', [], ['context' => 'arch_checkout_if_cart_empty']),
        'redirect_to_cart' => $this->t('Redirect to cart page', [], ['context' => 'arch_checkout_if_cart_empty']),
      ],
      '#weight' => -8,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements Form API #process callback.
   */
  public function processPluginOptions($element) {
    foreach ($this->checkoutTypeManager->getDefinitions() as $plugin_id => $plugin_definition) {
      if (isset($plugin_definition['description'])) {
        $element[$plugin_id]['#description'] = $plugin_definition['description'];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(self::CONFIG_NAME)
      ->set('plugin_id', $form_state->getValue('plugin_id'))
      ->set('anonymous_checkout', $form_state->getValue('anonymous_checkout'))
      ->set('redirect_to_cart_if_empty', $form_state->getValue('redirect_to_cart_if_empty'))
      ->save();

    /** @var \Drupal\arch_checkout\CheckoutType\CheckoutTypePluginInterface $plugin */
    $plugin = $this->checkoutTypeManager->createInstance($form_state->getValue('plugin_id'), []);
    $plugin->submitConfigurationForm($form, $form_state);
    parent::submitForm($form, $form_state);
  }

  /**
   * Get default checkout plugin ID.
   *
   * @return string|null
   *   Default plugin ID.s
   *
   * @throws \Drupal\arch_checkout\CheckoutType\Exception\CheckoutTypeException
   */
  protected function getDefaultPluginId() {
    $default_plugin = $this->checkoutTypeManager->getDefaultCheckoutType(FALSE);
    $default_plugin_id = NULL;
    if (!empty($default_plugin)) {
      $default_plugin_id = $default_plugin['id'];
    }
    return $default_plugin_id;
  }

}
