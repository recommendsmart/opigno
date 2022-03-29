<?php

namespace Drupal\Tests\eca_base\Kernel;

use Drupal\eca\Plugin\ECA\Condition\StringComparisonBase;
use Drupal\eca\PluginManager\Condition;
use Drupal\eca\Service\Conditions;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for the "eca_scalar" condition plugin.
 *
 * @group eca
 * @group eca_base
 */
class CompareScalarTest extends KernelTestBase {

  protected static $modules = [
    'eca',
    'eca_base',
  ];

  /**
   * @var \Drupal\eca\PluginManager\Condition|null
   */
  protected ?Condition $conditionManager;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->conditionManager = \Drupal::service('plugin.manager.eca.condition');
  }

  /**
   * Tests scalar value comparison.
   *
   * @dataProvider stringDataProvider
   * @dataProvider integerDataProvider
   */
  public function testScalarValues($left, $right, $operator, $type, $case, $negate, $message): void {
    // Configure default settings for condition.
    $config = [
      'left' => $left,
      'right' => $right,
      'operator' => $operator,
      'type' => $type,
      'case' => $case,
      'negate' => $negate,
    ];
    /** @var \Drupal\eca_base\Plugin\ECA\Condition\ScalarComparison $condition */
    $condition = $this->conditionManager->createInstance('eca_scalar', $config);
    $this->assertTrue($condition->evaluate(), $message);
  }

  /**
   * Provides multiple string test cases for the testScalarValues method.
   *
   * @return array
   *   The string test cases.
   */
  public function stringDataProvider(): array {
    return [
      [
        'my test string',
        'my test string',
        StringComparisonBase::COMPARE_EQUALS,
        StringComparisonBase::COMPARE_TYPE_VALUE,
        Conditions::OPTION_NO,
        Conditions::OPTION_NO,
        'Left equals right value.',
      ],
      [
        'my test string',
        'my test string',
        StringComparisonBase::COMPARE_EQUALS,
        StringComparisonBase::COMPARE_TYPE_VALUE,
        Conditions::OPTION_YES,
        Conditions::OPTION_NO,
        'Left equals (case sensitiv) right value.',
      ],
      [
        'my test string',
        'My Test String',
        StringComparisonBase::COMPARE_EQUALS,
        StringComparisonBase::COMPARE_TYPE_VALUE,
        Conditions::OPTION_YES,
        Conditions::OPTION_YES,
        'Left does not equal (case sensitiv) right value.',
      ],
      [
        'my test string',
        'my test',
        StringComparisonBase::COMPARE_BEGINS_WITH,
        StringComparisonBase::COMPARE_TYPE_VALUE,
        Conditions::OPTION_NO,
        Conditions::OPTION_NO,
        'Left begins with right value.',
      ],
      [
        'my test string',
        'test string',
        StringComparisonBase::COMPARE_ENDS_WITH,
        StringComparisonBase::COMPARE_TYPE_VALUE,
        Conditions::OPTION_NO,
        Conditions::OPTION_NO,
        'Left ends with right value.',
      ],
      [
        'my test string',
        'test',
        StringComparisonBase::COMPARE_CONTAINS,
        StringComparisonBase::COMPARE_TYPE_VALUE,
        Conditions::OPTION_NO,
        Conditions::OPTION_NO,
        'Left contains right value.',
      ],
      [
        'my test string',
        'a test string',
        StringComparisonBase::COMPARE_GREATERTHAN,
        StringComparisonBase::COMPARE_TYPE_VALUE,
        Conditions::OPTION_NO,
        Conditions::OPTION_NO,
        'Left is greater than right value.',
      ],
      [
        'my test string',
        'your test string',
        StringComparisonBase::COMPARE_LESSTHAN,
        StringComparisonBase::COMPARE_TYPE_VALUE,
        Conditions::OPTION_NO,
        Conditions::OPTION_NO,
        'Left is less than right value.',
      ],
      [
        'my test string',
        'my test string',
        StringComparisonBase::COMPARE_ATMOST,
        StringComparisonBase::COMPARE_TYPE_VALUE,
        Conditions::OPTION_NO,
        Conditions::OPTION_NO,
        'Left is at most the equal right value.',
      ],
      [
        'my test string',
        'your test string',
        StringComparisonBase::COMPARE_ATMOST,
        StringComparisonBase::COMPARE_TYPE_VALUE,
        Conditions::OPTION_NO,
        Conditions::OPTION_NO,
        'Left is at most right value.',
      ],
      [
        'my test string',
        'my test string',
        StringComparisonBase::COMPARE_ATLEAST,
        StringComparisonBase::COMPARE_TYPE_VALUE,
        Conditions::OPTION_NO,
        Conditions::OPTION_NO,
        'Left is at least the equal right value.',
      ],
      [
        'my test string',
        'a test string',
        StringComparisonBase::COMPARE_ATLEAST,
        StringComparisonBase::COMPARE_TYPE_VALUE,
        Conditions::OPTION_NO,
        Conditions::OPTION_NO,
        'Left is at least right value.',
      ],
    ];
  }

  /**
   * Provides multiple integer test cases for the testScalarValues method.
   *
   * @return array
   *   The integer test cases.
   */
  public function integerDataProvider(): array {
    return [
      [
        5,
        5,
        StringComparisonBase::COMPARE_EQUALS,
        StringComparisonBase::COMPARE_TYPE_NUMERIC,
        Conditions::OPTION_NO,
        Conditions::OPTION_NO,
        'Left and right are equal.',
      ],
      [
        5,
        4,
        StringComparisonBase::COMPARE_GREATERTHAN,
        StringComparisonBase::COMPARE_TYPE_NUMERIC,
        Conditions::OPTION_NO,
        Conditions::OPTION_NO,
        'Left is great than right value.',
      ],
    ];
  }

}
