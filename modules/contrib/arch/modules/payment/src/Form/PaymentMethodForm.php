<?php

namespace Drupal\arch_payment\Form;

use Drupal\arch\ConfigurableArchPluginInterface;
use Drupal\arch_payment\PaymentMethodInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default payment method configure form.
 *
 * @package Drupal\arch_payment\Form
 */
class PaymentMethodForm extends FormBase implements PaymentMethodFormInterface {

  /**
   * Payment method.
   *
   * @var \Drupal\arch_payment\PaymentMethodInterface
   */
  protected $paymentMethod;

  /**
   * Currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|null
   */
  protected $currencyStorage;

  /**
   * PaymentMethodForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface|null $currencyStorage
   *   Currency storage.
   */
  public function __construct(
    EntityStorageInterface $currencyStorage
  ) {
    $this->currencyStorage = $currencyStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('currency')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payment_method_configure';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PaymentMethodInterface $payment_method = NULL) {
    $form = [];

    $this->paymentMethod = $payment_method;
    $form['#payment_method'] = $payment_method;
    $form['#title'] = $this->t('Configure "<em>@title</em>" payment method', [
      '@title' => $payment_method->getAdminLabel(),
    ]);

    $form['settings'] = [
      '#tree' => TRUE,
    ];

    $form['settings']['status'] = [
      '#type' => 'checkbox',
      '#default_value' => $payment_method->isActive(),
      '#title' => $this->t('Enabled', [], ['context' => 'arch_payment_method']),
      '#weight' => -100,
    ];

    $form['fees'] = [
      '#type' => 'vertical_tabs',
      '#group' => 'settings',
    ];

    $form['default'] = [
      '#tree' => TRUE,
      '#type' => 'details',
      '#group' => 'fees',
      '#parents' => [
        'settings',
        'fees',
        'default',
      ],
      '#title' => $this->t('Payment fee', [], ['context' => 'arch_payment_method']),
    ] + $this->paymentFee($form_state);

    if ($this->paymentMethod instanceof ConfigurableArchPluginInterface) {
      $this->paymentMethod->configFormAlter($form, $form_state);
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($this->paymentMethod instanceof ConfigurableArchPluginInterface) {
      $this->paymentMethod->configFormValidate($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->paymentMethod instanceof ConfigurableArchPluginInterface) {
      $this->paymentMethod->configFormPreSubmit($form, $form_state);
    }

    $enabled = $form_state->getValue(['settings', 'status']);
    if ($enabled) {
      $this->paymentMethod->enable();
    }
    else {
      $this->paymentMethod->disable();
    }

    $settings = $form_state->getValue('settings');
    $skipp = ['status'];
    foreach ($settings as $key => $value) {
      if (in_array($key, $skipp)) {
        continue;
      }

      $this->paymentMethod->setSetting($key, $value);
    }

    if ($this->paymentMethod instanceof ConfigurableArchPluginInterface) {
      $this->paymentMethod->configFormPostSubmit($form, $form_state);
    }
  }

  /**
   * Default payment fee settings.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state data.
   *
   * @return array
   *   Array of default payment fee settings.
   */
  public function paymentFee(FormStateInterface $formState) {
    $feesDefaultValues = $this->paymentMethod->getSetting('fees');
    $defaultValues = $feesDefaultValues['default'];

    $paymentFee = [
      'fee' => [
        '#type' => 'number',
        '#title' => $this->t('Default payment fee', [], ['context' => 'arch_payment_method']),
        '#step' => 0.1,
        '#min' => 0,
        '#default_value' => (isset($defaultValues['fee']) ? $defaultValues['fee'] : 0),
        '#description' => $this->t('You can set a payment fee for this payment method. The step is in 0.1 format.'),
      ],
      'currency' => [
        '#type' => 'textfield',
        '#title' => $this->t('Currency', [], ['context' => 'arch_payment_method']),
        '#default_value' => (isset($defaultValues['currency']) ? $defaultValues['currency'] : 'XXX'),
        '#description' => $this->t('Currency of the payment fee.'),
      ],
      'vat_rate' => [
        '#type' => 'number',
        '#title' => $this->t('VAT Rate', [], ['context' => 'arch_payment_method']),
        '#step' => 1,
        '#min' => 0,
        '#max' => 100,
        '#default_value' => (isset($defaultValues['vat_rate']) ? $defaultValues['vat_rate'] : 0),
      ],
    ];

    if (!empty($this->currencyStorage)) {
      $options = [];
      foreach ($this->currencyStorage->loadMultiple() as $currency) {
        $options[$currency->id()] = $currency->label();
      }
      $paymentFee['currency']['#type'] = 'select';
      $paymentFee['currency']['#options'] = $options;
    }

    return $paymentFee;
  }

}
