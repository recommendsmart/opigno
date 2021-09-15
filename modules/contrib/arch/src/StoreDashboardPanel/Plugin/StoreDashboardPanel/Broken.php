<?php

namespace Drupal\arch\StoreDashboardPanel\Plugin\StoreDashboardPanel;

use Drupal\arch\StoreDashboardPanel\StoreDashboardPanel;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a fallback plugin for missing dashboard panel plugins.
 *
 * @StoreDashboardPanel(
 *   id = "broken",
 *   admin_label = @Translation("Broken/Missing"),
 * )
 */
class Broken extends StoreDashboardPanel {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->brokenMessage();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return $this->brokenMessage();
  }

  /**
   * Generate message with debugging information as to why the block is broken.
   *
   * @return array
   *   Render array containing debug information.
   */
  protected function brokenMessage() {
    $build['message'] = [
      '#markup' => $this->t('This panel is broken or missing. You may be missing content or you might need to enable the original module.', [], ['context' => 'arch_dashboard']),
    ];

    return $build;
  }

}
