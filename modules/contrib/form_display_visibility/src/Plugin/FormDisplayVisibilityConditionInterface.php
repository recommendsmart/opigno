<?php

namespace Drupal\form_display_visibility\Plugin;

/**
 * Provides an interface for FormDisplayVisibilityCondition plugins.
 */
interface FormDisplayVisibilityConditionInterface {

  /**
   * Builds the configuration form for each condition.
   *
   * @return array
   *   The form.
   */
  public function buildForm();

  /**
   * Calculates the access level for a given condition.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result of the calculation.
   */
  public function applyCondition();

  /**
   * Displays a summary on the widget information.
   *
   * @return string
   *   The summary to display.
   */
  public function displaySummary();

}
