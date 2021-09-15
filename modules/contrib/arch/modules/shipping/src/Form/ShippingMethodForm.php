<?php

namespace Drupal\arch_shipping\Form;

use Drupal\arch\ConfigurableArchPluginInterface;
use Drupal\arch_shipping\ShippingMethodInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Default shipping method configure form.
 *
 * @package Drupal\arch_shipping\Form
 */
class ShippingMethodForm extends FormBase implements ShippingMethodFormInterface {

  /**
   * Shipping method.
   *
   * @var \Drupal\arch_shipping\ShippingMethodInterface
   */
  protected $shippingMethod;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shipping_method_configure';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ShippingMethodInterface $shipping_method = NULL) {
    $form = [];
    $form['#parents'] = [];

    $this->shippingMethod = $shipping_method;
    $form['#shipping_method'] = $shipping_method;
    $form['#title'] = $this->t('Configure "<em>@title</em>" shipping method', [
      '@title' => $shipping_method->getAdminLabel(),
    ]);

    $form['settings'] = [
      '#tree' => TRUE,
    ];

    $form['settings']['status'] = [
      '#type' => 'checkbox',
      '#default_value' => $shipping_method->isActive(),
      '#title' => $this->t('Enabled', [], ['context' => 'arch_shipping_method']),
      '#weight' => -100,
    ];

    if ($this->shippingMethod instanceof ConfigurableArchPluginInterface) {
      $this->shippingMethod->configFormAlter($form, $form_state);
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
    if ($this->shippingMethod instanceof ConfigurableArchPluginInterface) {
      $this->shippingMethod->configFormValidate($form, $form_state);
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->shippingMethod instanceof ConfigurableArchPluginInterface) {
      $this->shippingMethod->configFormPreSubmit($form, $form_state);
    }

    $enabled = $form_state->getValue(['settings', 'status']);
    if ($enabled) {
      $this->shippingMethod->enable();
    }
    else {
      $this->shippingMethod->disable();
    }

    $settings = $form_state->getValue('settings');
    $skipp = ['status'];
    foreach ($settings as $key => $value) {
      if (in_array($key, $skipp)) {
        continue;
      }

      $this->shippingMethod->setSetting($key, $value);
    }

    if ($this->shippingMethod instanceof ConfigurableArchPluginInterface) {
      $this->shippingMethod->configFormPostSubmit($form, $form_state);
    }
  }

}
