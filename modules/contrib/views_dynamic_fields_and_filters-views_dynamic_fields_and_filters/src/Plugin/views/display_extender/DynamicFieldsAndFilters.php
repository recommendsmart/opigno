<?php

namespace Drupal\views_dynamic_fields_and_filters\Plugin\views\display_extender;

use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * DynamicFieldsAndFilters display extender plugin.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "views_dynamic_fields_and_filters",
 *   title = @Translation("Dynamic fields and filters"),
 *   help = @Translation("Dynamically show and apply fields and filters based on an exposed filter."),
 *   no_ui = FALSE
 * )
 */
class DynamicFieldsAndFilters extends DisplayExtenderPluginBase {

  /**
   * {@inheritdoc}
   *
   * Provide the options for this plugin.
   */
  public function defineOptionsAlter(&$options) {
    $filters = [];
    for ($i = 1; $i < 10; $i++) {
      $filters["dff" . $i] = ['default' => ''];
    }
    $options['views_dynamic_fields_and_filters']['contains'] = $filters;
  }

  /**
   * {@inheritdoc}
   *
   * Provide the default summary for options and category in the views UI.
   */
  public function optionsSummary(&$categories, &$options) {
    $categories['views_dynamic_fields_and_filters'] = [
      'title' => t('Dynamic fields and filters'),
      'column' => 'third',
    ];

    $options['views_dynamic_fields_and_filters'] = [
      'category' => 'views_dynamic_fields_and_filters',
      'title' => t('Based on'),
      'value' => $this->getBaseFiltersFormatted(),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Provide a form to edit options for this plugin.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    if ($form_state->get('section') == 'views_dynamic_fields_and_filters' && $form_state) {

      $filters = $this->getBaseFilters();
      $form['#title'] .= t('Dynamic fields and filters for this display');

      $form['views_dynamic_fields_and_filters']['#type'] = 'container';

      $form['views_dynamic_fields_and_filters']['tabs'] = [
        '#type' => 'vertical_tabs',
        '#default_tab' => 'edit-fieldset',
      ];

      $form['views_dynamic_fields_and_filters']['base_filters'] = [
        '#type' => 'details',
        '#title' => $this->t('Base filters'),
        '#group' => 'tabs',
      ];
      $form['views_dynamic_fields_and_filters']['base_filters']['#tree'] = TRUE;
      for ($i = 1; $i < 10; $i++) {
        $form['views_dynamic_fields_and_filters']['base_filters']['dff' . $i] = [
          '#title' => 'dff' . $i,
          '#type' => 'textfield',
          '#placeholder' => $this->t('Enter a "filter identifier" / any query parameter name'),
          '#default_value' => !empty($filters['dff' . $i]) ? $filters['dff' . $i] : '',
        ];
      }

      $form['views_dynamic_fields_and_filters']['description'] = [
        '#type' => 'details',
        '#title' => $this->t('Usuage'),
        '#group' => 'tabs',
      ];
      $form['views_dynamic_fields_and_filters']['description']['usuage_1'] = [
        '#type' => 'item',
        '#title' => $this->t('How to use dynamic fields and filters.'),
        '#description' => $this->t('You find the documentation directly on the Project Page.'),
      ];
      $form['views_dynamic_fields_and_filters']['description']['usuage_link'] = [
        '#type' => 'item',
        '#markup' => '<a href="https://www.drupal.org/project/views_dynamic_fields_and_filters" target="_blank">Views Dynamic fields and filters Project Page</a>',
      ];

    }
  }

  /**
   * {@inheritdoc}
   *
   * Validate the options form.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') == 'views_dynamic_fields_and_filters') {
      $base_filters = $form_state->getValue('base_filters');
      foreach ($base_filters as $key => $filter) {
        if (preg_match('/\s/', $filter)) {
          $el = $form['views_dynamic_fields_and_filters']['base_filters'][$key];
          $form_state->setError($el, $this->t("A base filter (query parameter name) must not contain whitespace!"));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Handle any special handling on the validate form.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') == 'views_dynamic_fields_and_filters') {
      $base_filters = $form_state->getValue('base_filters');
      $this->options['views_dynamic_fields_and_filters'] = $base_filters;
    }
  }

  /**
   * Get the base filter configuration for this display.
   *
   * @return array
   *   The base filter configuration for this display.
   */
  public function getBaseFilters() {
    if (!empty($this->options['views_dynamic_fields_and_filters'])) {
      return $this->options['views_dynamic_fields_and_filters'];
    }
    return [];
  }

  /**
   * Format filters to a string to be shown in the optionsSummary.
   *
   * @return string
   *   The formatted filters.
   */
  public function getBaseFiltersFormatted() {
    $base_filters = array_filter($this->getBaseFilters());
    if (!empty($base_filters)) {
      return http_build_query($base_filters, "", ", ");
    }
    return t('None');
  }

  /**
   * Return the configurated filters and their selected values.
   *
   * @return array
   *   The base filters with the values from current request.
   */
  public function getBaseFiltersWithValues() {
    $base_filters = $this->getBaseFilters();
    $base_filters_with_values = [];
    foreach ($base_filters as $key => $filter_name) {
      if (!empty($filter_name)) {
        $base_filters_with_values[$key] = [
          "name" => $filter_name,
          "value" => $this->view->getRequest()->get($filter_name),
        ];
      }
    }
    return $base_filters_with_values;
  }

  /**
   * Compare supplied value with supplied condition.
   *
   * @param string|array $value
   *   The value(s) to evaluate the condition on.
   * @param string $condition
   *   Either the string to match or a more detailed expression.
   *
   * @return bool
   *   The test result.
   */
  public function evaluateCondition($value, $condition) {

    // If value is an array return true if one of entries matches.
    if (is_array($value)) {
      $result = FALSE;
      foreach ($value as $v) {
        if (!$result) {
          $result = $this->evaluateCondition($v, $condition);
        }
      }
      return $result;
    }

    $expression = FALSE;
    // Check for value expressions (enclosed in braces).
    if (preg_match('/^{(.*)}$/', $condition, $matches)) {
      $expression = isset($matches[1]) ? explode(':', $matches[1], 2) : FALSE;

      // No "expression:value" pattern found.
      if ($expression && count($expression) != 2) {
        $expression = FALSE;
      }
      // Expression not recognized.
      if ($expression && !preg_match('/^(neq|in|nin|gt|lt|cn|ncn)$/', $expression[0])) {
        $expression = FALSE;
      }
    }

    if (!$expression) {
      return $value == $condition;
    }
    switch ($expression[0]) {
      case 'neq':
        return $value != $expression[1];

      case 'in':
        return in_array($value, explode(',', $expression[1]));

      case 'nin':
        return !in_array($value, explode(',', $expression[1]));

      case 'gt':
        return $value > $expression[1];

      case 'lt':
        return $value < $expression[1];

      case 'cn':
        return strpos($value, $expression[1]) !== FALSE;

      case 'ncn':
        return strpos($value, $expression[1]) === FALSE;

    }
  }

  /**
   * Extracts conditions from a string and evaluates them.
   *
   * @param string $admin_label
   *   The string to extract the conditions from.
   *
   * @return bool
   *   The test result.
   */
  public function testLabel($admin_label) {
    $base_filters = $this->getBaseFiltersWithValues();
    $split = explode("|", $admin_label);

    // Check if at least one "basefilter|condition|" pattern exists.
    if (count($split) < 3 || !preg_match('/^dff([1-9])$/', $split[0])) {
      return FALSE;
    }
    $filter1 = $split[0];
    $condition1 = $split[1];

    // |operator|basefilter|condition| Each combination consists of 3 parts.
    // Support up to 10 combinations.
    $combinations = [];
    for ($i = 2; $i <= 32; $i++) {

      // Check if 1st part is operator.
      if (!empty($split[$i]) && preg_match('/^(AND|OR|XOR)$/', $split[$i])) {

        // Check if 2nd is basefilter and 3rd is condition.
        if (!empty($split[$i + 1]) && preg_match('/^dff([1-9])$/', $split[$i + 1]) && isset($split[$i + 2])) {
          $operator = $split[$i];
          $filterx = $split[$i + 1];
          $conditionx = $split[$i + 2];
          $resultx = FALSE;
          if (array_key_exists($filterx, $base_filters)) {
            $resultx = $this->evaluateCondition($base_filters[$filterx]['value'], $conditionx);
          }
          $combinations[] = [
            "operator" => $operator,
            "result" => $resultx,
          ];
        }
      }
    }

    $result = FALSE;
    if (array_key_exists($filter1, $base_filters)) {
      $result = $this->evaluateCondition($base_filters[$filter1]['value'], $condition1);
    }

    if (empty($combinations)) {
      return $result;
    }

    foreach ($combinations as $combination) {
      if ($combination['operator'] == "AND") {
        $result = $result && $combination['result'];
      }
      if ($combination['operator'] == "OR") {
        $result = $result || $combination['result'];
      }
      if ($combination['operator'] == "XOR") {
        $result = $result xor $combination['result'];
      }
    }

    return $result;
  }

  /**
   * Check whether a given label starts with "dff" before doing further tests.
   *
   * @param string $admin_label
   *   The string to extract the conditions from.
   *
   * @return bool
   *   The test result.
   */
  public function isDffLabel($admin_label) {
    return preg_match('/^dff([1-9])/', $admin_label);
  }

  /**
   * Add query_args cache context if cache not disabled.
   *
   * Some formats i.e "Serializer"/"Rss-feed" do not know their
   * response changes if fields are enabled/disabled with dff.
   * By adding the url.query_args context we ensure that for each
   * response variation caused by query, there is a seperate cache entry.
   */
  public function extendCacheIfEnabled() {
    $opts = $this->view->getDisplay()->options;
    if (!$opts['defaults']['cache'] || $opts['cache']['type'] == 'none') {
      return;
    }
    $this->view->addCacheContext('url.query_args');
  }

}
