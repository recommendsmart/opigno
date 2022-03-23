<?php

namespace Drupal\eca\Plugin\ECA\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Service\Conditions;
use Drupal\eca\Plugin\OptionsInterface;

/**
 * Base class for ECA condition plugins to compare strings.
 */
abstract class StringComparisonBase extends ConditionBase implements OptionsInterface {

  public const COMPARE_EQUALS = 'equal';
  public const COMPARE_BEGINS_WITH = 'beginswith';
  public const COMPARE_ENDS_WITH = 'endswith';
  public const COMPARE_CONTAINS = 'contains';
  public const COMPARE_GREATERTHAN = 'greaterthan';
  public const COMPARE_LESSTHAN = 'lessthan';
  public const COMPARE_ATMOST = 'atmost';
  public const COMPARE_ATLEAST = 'atleast';

  public const COMPARE_TYPE_VALUE = 'value';
  public const COMPARE_TYPE_COUNT = 'count';
  public const COMPARE_TYPE_LEXICAL = 'lexical';
  public const COMPARE_TYPE_NATURAL = 'natural';
  public const COMPARE_TYPE_NUMERIC = 'numeric';

  /**
   * This flag indicates whether Token replacement should be applied beforehand.
   *
   * @var bool
   */
  protected static bool $replaceTokens = TRUE;

  /**
   * Get the first string value for comparison.
   *
   * @return string
   */
  abstract protected function getFirstValue(): string;

  /**
   * Get the second string value for comparison.
   *
   * @return string
   */
  abstract protected function getSecondValue(): string;

  /**
   * Get the selected comparison operator.
   *
   * @return string
   *   The comparison operator.
   */
  protected function getOperator(): string {
    return $this->configuration['operator'] ?? static::COMPARE_EQUALS;
  }

  /**
   * Get the comparison type.
   *
   * @return string
   *   The comparison type.
   */
  protected function getType(): string {
    return $this->configuration['type'] ?? static::COMPARE_TYPE_VALUE;
  }

  /**
   * Whether the comparison is case sensitive or not.
   *
   * @return bool
   *   Returns TRUE if case sensitive, FALSE otherwise.
   */
  protected function caseSensitive(): bool {
    return $this->configuration['case'] === Conditions::OPTION_YES;
  }

  /**
   * {@inheritdoc}
   */
  final public function evaluate(): bool {
    if (static::$replaceTokens) {
      $value1 = $this->tokenServices->replaceClear($this->getFirstValue());
      $value2 = $this->tokenServices->replaceClear($this->getSecondValue());
    }
    else {
      $value1 = $this->getFirstValue();
      $value2 = $this->getSecondValue();
    }

    if (!$this->caseSensitive()) {
      $value1 = mb_strtolower($value1);
      $value2 = mb_strtolower($value2);
    }

    switch ($this->getType()) {
      case static::COMPARE_TYPE_NUMERIC:
        if (!is_numeric($value1) || !is_numeric($value2)) {
          return FALSE;
        }
        break;

      case static::COMPARE_TYPE_LEXICAL:
        // Prepend the value with a constant character to force string
        // comparison, even if values were numeric.
        $value1 = 'a' . $value1;
        $value2 = 'a' . $value2;
        break;

      case static::COMPARE_TYPE_NATURAL:
        $value1 = strnatcmp($value1, $value2);
        $value2 = 0;
        break;

      case static::COMPARE_TYPE_COUNT:
        $value1 = mb_strlen($value1);
        $value2 = mb_strlen($value2);
        break;
    }

    $result = FALSE;

    switch ($this->getOperator()) {
      case static::COMPARE_EQUALS:
        $result = $value1 === $value2;
        break;

      case static::COMPARE_BEGINS_WITH:
        $result = mb_strpos($value2, $value1) === 0;
        break;

      case static::COMPARE_ENDS_WITH:
        $result = mb_strpos($value2, $value1) === (mb_strlen($value2) - mb_strlen($value1));
        break;

      case static::COMPARE_CONTAINS:
        $result = mb_strpos($value2, $value1) !== FALSE;
        break;

      case static::COMPARE_GREATERTHAN:
        $result = $value2 > $value1;
        break;

      case static::COMPARE_LESSTHAN:
        $result = $value2 < $value1;
        break;

      case static::COMPARE_ATMOST:
        $result = $value2 <= $value1;
        break;

      case static::COMPARE_ATLEAST:
        $result = $value2 >= $value1;
        break;
    }

    return $this->negationCheck($result);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
        'operator' => static::COMPARE_EQUALS,
        'type' => static::COMPARE_TYPE_VALUE,
        'case' => FALSE,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Comparison operator'),
      '#default_value' => $this->getOperator(),
      '#options' => $this->getOptions('operator'),
      '#weight' => -9,
    ];
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Comparison type'),
      '#default_value' => $this->getType(),
      '#options' => $this->getOptions('type'),
      '#weight' => -5,
    ];
    $form['case'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Case sensitive comparison'),
      '#default_value' => $this->caseSensitive(),
      '#weight' => -4,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['operator'] = $form_state->getValue('operator');
    $this->configuration['type'] = $form_state->getValue('type');
    $this->configuration['case'] = $form_state->getValue('case');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(string $id): ?array {
    if ($id === 'operator') {
      return [
        static::COMPARE_EQUALS => $this->t('equals'),
        static::COMPARE_BEGINS_WITH => $this->t('begins with'),
        static::COMPARE_ENDS_WITH => $this->t('ends with'),
        static::COMPARE_CONTAINS => $this->t('contains'),
        static::COMPARE_GREATERTHAN => $this->t('greater than'),
        static::COMPARE_LESSTHAN => $this->t('less than'),
        static::COMPARE_ATMOST => $this->t('at most'),
        static::COMPARE_ATLEAST => $this->t('at least'),
      ];
    }
    if ($id === 'type') {
      return [
        static::COMPARE_TYPE_VALUE => $this->t('Value'),
        static::COMPARE_TYPE_NATURAL => $this->t('Natural order'),
        static::COMPARE_TYPE_NUMERIC => $this->t('Numeric order'),
        static::COMPARE_TYPE_LEXICAL => $this->t('Lexical order'),
        static::COMPARE_TYPE_COUNT => $this->t('Character count'),
      ];
    }
    return NULL;
  }

}
