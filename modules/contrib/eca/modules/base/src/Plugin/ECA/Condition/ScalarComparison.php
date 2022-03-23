<?php

namespace Drupal\eca_base\Plugin\ECA\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\StringComparisonBase;

/**
 * ECA condition plugin for comparing two scalar values.
 *
 * @EcaCondition(
 *   id = "eca_scalar",
 *   label = @Translation("Compare two scalar values")
 * )
 */
class ScalarComparison extends StringComparisonBase {

  /**
   * {@inheritdoc}
   */
  protected function getFirstValue(): string {
    return $this->configuration['right'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getSecondValue(): string {
    return $this->configuration['left'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'left' => '',
      'right' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['right'] = [
      '#type' => 'textarea',
      '#title' => $this->t('First value'),
      '#default_value' => $this->getSecondValue(),
      '#weight' => -10,
    ];
    $form['left'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Second value'),
      '#default_value' => $this->getFirstValue(),
      '#weight' => -8,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['left'] = $form_state->getValue('left');
    $this->configuration['right'] = $form_state->getValue('right');
    parent::submitConfigurationForm($form, $form_state);
  }

}
