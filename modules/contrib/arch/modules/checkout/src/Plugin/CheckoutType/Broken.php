<?php

namespace Drupal\arch_checkout\Plugin\CheckoutType;

use Drupal\arch_checkout\CheckoutType\CheckoutType;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a fallback plugin for missing checkout type plugins.
 *
 * @CheckoutType(
 *   id = "broken",
 *   label = @Translation("Broken/Missing"),
 *   admin_label = @Translation("Broken/Missing"),
 *   description = @Translation("Displays an information about the missing plugin.")
 * )
 */
class Broken extends CheckoutType {

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
      '#markup' => $this->t('This checkout type is broken or missing. You may be missing content or you might need to enable the original module.', [], ['context' => 'arch_checkout']),
    ];

    return $build;
  }

}
