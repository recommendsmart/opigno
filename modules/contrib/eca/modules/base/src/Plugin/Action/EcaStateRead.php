<?php

namespace Drupal\eca_base\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;

/**
 * Action to read value from ECA's key value store and store it as token.
 *
 * @Action(
 *   id = "eca_state_read",
 *   label = @Translation("Persistent state: read")
 * )
 */
class EcaStateRead extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $value = $this->state->get($this->configuration['key'], '');
    $this->tokenServices->addTokenData($this->configuration['token_name'], $value);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
        'key' => '',
        'token_name' => '',
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
    $form['token_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of token'),
      '#default_value' => $this->configuration['token_name'],
      '#weight' => -9,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['key'] = $form_state->getValue('key');
    $this->configuration['token_name'] = $form_state->getValue('token_name');
    parent::submitConfigurationForm($form, $form_state);
  }

}
