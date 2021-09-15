<?php

namespace Drupal\arch_payment;

use Drupal\arch\ConfigurableArchPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsTrait;

/**
 * Payment method base.
 *
 * @package Drupal\arch_payment
 */
abstract class ConfigurablePaymentMethodBase extends PaymentMethodBase implements ConfigurableArchPluginInterface {

  use PluginWithFormsTrait;

  /**
   * {@inheritdoc}
   */
  public function configFormAlter(array &$form, FormStateInterface $form_state) {
    $plugin_configuration_form = $this->getPluginForm();
    if ($plugin_configuration_form instanceof PluginFormInterface) {
      $form['#tree'] = TRUE;
      $form['#parents'] = [];
      $form['plugin_configuration'] = [];
      $form['plugin_configuration']['#parents'] = ['plugin_configuration'];
      $subform_state = SubformState::createForSubform($form['plugin_configuration'], $form, $form_state);
      $form['plugin_configuration'] = $plugin_configuration_form->buildConfigurationForm($form['plugin_configuration'], $subform_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configFormValidate(array &$form, FormStateInterface $form_state) {
    $plugin_configuration_form = $this->getPluginForm();
    if ($plugin_configuration_form instanceof PluginFormInterface) {
      $subform_state = SubformState::createForSubform($form['plugin_configuration'], $form, $form_state);
      $plugin_configuration_form->validateConfigurationForm($form['plugin_configuration'], $subform_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configFormPreSubmit(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function configFormPostSubmit(array &$form, FormStateInterface $form_state) {
    $plugin_configuration_form = $this->getPluginForm();
    if ($plugin_configuration_form instanceof PluginFormInterface) {
      $subform_state = SubformState::createForSubform($form['plugin_configuration'], $form, $form_state);
      $plugin_configuration_form->submitConfigurationForm($form['plugin_configuration'], $subform_state);
    }
  }

  /**
   * Get config form instance.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   Form instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getConfigForm() {
    return $this->pluginFormFactory->createInstance($this, 'configure');
  }

  /**
   * Get plugin configure form.
   *
   * @return $this|\Drupal\Core\Plugin\PluginFormInterface|null
   *   Form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getPluginForm() {
    if ($this instanceof PluginFormInterface) {
      return $this;
    }

    if ($this->hasFormClass('configure')) {
      return $this->getConfigForm();
    }

    return NULL;
  }

}
