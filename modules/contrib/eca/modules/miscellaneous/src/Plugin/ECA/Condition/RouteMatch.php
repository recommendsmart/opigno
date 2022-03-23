<?php

namespace Drupal\eca_misc\Plugin\ECA\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Plugin\ECA\Condition\StringComparisonBase;

/**
 * Plugin implementation of the ECA condition for entity type and bundle.
 *
 * @EcaCondition(
 *   id = "eca_route_match",
 *   label = "Route match"
 * )
 */
class RouteMatch extends StringComparisonBase {

  /**
   * {@inheritdoc}
   */
  protected function getFirstValue(): string {
    if ($request = $this->requestStack->getCurrentRequest()) {
      return $request->getBasePath();
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function getSecondValue(): string {
    return $this->configuration['path'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
        'path' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#default_value' => $this->configuration['path'],
      '#weight' => -8,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['path'] = $form_state->getValue('path');
    parent::submitConfigurationForm($form, $form_state);
  }

}
