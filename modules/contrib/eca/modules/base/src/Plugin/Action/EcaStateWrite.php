<?php

namespace Drupal\eca_base\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;

/**
 * Action to store arbitrary value to ECA's key value store.
 *
 * @Action(
 *   id = "eca_state_write",
 *   label = @Translation("Persistent state: write")
 * )
 */
class EcaStateWrite extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $this->state->set($this->configuration['key'], $this->tokenServices->replaceClear($this->configuration['value']));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'key' => '',
      'value' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('State key'),
      '#default_value' => $this->configuration['key'],
      '#weight' => -10,
    ];
    $form['value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Value of the token'),
      '#default_value' => $this->configuration['value'],
      '#weight' => -9,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['key'] = $form_state->getValue('key');
    $this->configuration['value'] = $form_state->getValue('value');
    parent::submitConfigurationForm($form, $form_state);
  }

}
