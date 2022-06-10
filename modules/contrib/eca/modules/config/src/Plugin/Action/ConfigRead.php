<?php

namespace Drupal\eca_config\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Action to read configuration.
 *
 * @Action(
 *   id = "eca_config_read",
 *   label = @Translation("Config: read"),
 *   description = @Translation("Read configuration and store it as a token.")
 * )
 */
class ConfigRead extends ConfigActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $token = $this->tokenServices;
    $token_name = $this->configuration['token_name'];
    $config_name = $token->replace($this->configuration['config_name']);
    $config_key = $this->configuration['config_key'] !== '' ? (string) $token->replace($this->configuration['config_key']) : '';
    $include_overridden = $this->configuration['include_overridden'];
    $config_factory = $this->getConfigFactory();

    $config = $include_overridden ? $config_factory->get($config_name) : $config_factory->getEditable($config_name);
    $value = $config->get($config_key);

    $token->addTokenData($token_name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'token_name' => '',
      'include_overridden' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['include_overridden'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include overridden'),
      '#description' => $this->t('Whether to apply module and settings.php overrides to values.'),
      '#default_value' => $this->configuration['include_overridden'],
      '#weight' => -7,
    ];
    $form['token_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of token'),
      '#default_value' => $this->configuration['token_name'],
      '#description' => $this->t('The targeted configuration value will be loaded into this specified token.'),
      '#weight' => -6,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['token_name'] = $form_state->getValue('token_name');
    $this->configuration['include_overridden'] = $form_state->getValue('include_overridden');
    parent::submitConfigurationForm($form, $form_state);
  }

}
