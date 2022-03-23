<?php

namespace Drupal\eca\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Configure ECA settings for this site.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'eca_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['eca.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['log_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Log level'),
      '#options' => RfcLogLevel::getLevels(),
      '#default_value' => $this->config('eca.settings')->get('log_level'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\Core\Config\Config $config */
    if ($config = $this->config('eca.settings')) {
      $config->set('log_level', $form_state->getValue('log_level'));
      $config->save();
    }
    parent::submitForm($form, $form_state);
  }

}
