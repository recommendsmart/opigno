<?php

namespace Drupal\eca_log\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca\Plugin\OptionsInterface;

/**
 * Write a log message.
 *
 * @Action(
 *   id = "eca_write_log_message",
 *   label = @Translation("Log Message")
 * )
 */
class LogMessage extends ConfigurableActionBase implements OptionsInterface {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $channel = $this->tokenServices->replaceClear($this->configuration['channel']);
    if (empty($channel)) {
      $channel = 'eca';
    }
    $severity = (int) $this->configuration['severity'];
    $message = $this->configuration['message'];
    $context = [];
    foreach ($this->tokenServices->scan($message) as $type => $tokens) {
      $replacements = $this->tokenServices->generate($type, $tokens, [], ['clear' => TRUE], new BubbleableMetadata());
      foreach ($replacements as $original_token => $replacement_value) {
        $context_argument = '%token__' . mb_substr(str_replace(':', '_', $original_token), 1, -1);
        $message = str_replace($original_token, $context_argument, $message);
        $context[$context_argument] = $replacement_value;
      }
    }
    \Drupal::logger($channel)->log($severity, $message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'channel' => '',
      'severity' => '',
      'message' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['channel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Channel'),
      '#default_value' => $this->configuration['channel'],
      '#weight' => -10,
    ];
    $form['severity'] = [
      '#type' => 'select',
      '#title' => $this->t('Severity'),
      '#default_value' => $this->configuration['severity'],
      '#options' => $this->getOptions('severity'),
      '#weight' => -9,
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#default_value' => $this->configuration['message'],
      '#weight' => -8,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['channel'] = $form_state->getValue('channel');
    $this->configuration['severity'] = $form_state->getValue('severity');
    $this->configuration['message'] = $form_state->getValue('message');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(string $id): ?array {
    if ($id === 'severity') {
      return RfcLogLevel::getLevels();
    }
    return NULL;
  }

}
