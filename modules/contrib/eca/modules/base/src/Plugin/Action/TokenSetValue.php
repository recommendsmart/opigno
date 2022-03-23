<?php

namespace Drupal\eca_base\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;

/**
 * Action to set an arbitrary token value.
 *
 * @Action(
 *   id = "eca_token_set_value",
 *   label = @Translation("Token: set value")
 * )
 */
class TokenSetValue extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $token = $this->tokenServices;
    $name = $this->configuration['token_name'];
    $value = $this->configuration['token_value'];
    // Allow direct assignment of available data from the Token environment.
    $value = (mb_strlen($value) <= 255) && $token->hasTokenData($value) ? $token->getTokenData($value) : $token->replaceClear($value);
    $token->addTokenData($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'token_name' => '',
      'token_value' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['token_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of token'),
      '#default_value' => $this->configuration['token_name'],
      '#weight' => -10,
    ];
    $form['token_value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Value of the token'),
      '#default_value' => $this->configuration['token_value'],
      '#weight' => -9,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['token_name'] = $form_state->getValue('token_name');
    $this->configuration['token_value'] = $form_state->getValue('token_value');
    parent::submitConfigurationForm($form, $form_state);
  }

}
