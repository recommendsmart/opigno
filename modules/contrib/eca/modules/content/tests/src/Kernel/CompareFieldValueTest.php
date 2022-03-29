<?php

namespace Drupal\Tests\eca_content\Kernel;

use Drupal\eca\Plugin\ECA\Condition\StringComparisonBase;
use Drupal\eca\PluginManager\Condition;
use Drupal\eca\Service\Conditions;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

/**
 * Kernel tests for the "eca_entity_field_value" condition plugin.
 *
 * @group eca
 * @group eca_content
 */
class CompareFieldValueTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'node',
    'eca',
    'eca_content',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(static::$modules);
    User::create(['uid' => 1, 'name' => 'admin'])->save();
  }

  /**
   * Evaluates the given test values with a fresh condition plugin.
   *
   * @param \Drupal\eca\PluginManager\Condition $condition_manager
   * @param \Drupal\node\NodeInterface $node
   * @param array $defaults
   * @param array $test_values
   *
   * @return void
   */
  private function evaluate(Condition $condition_manager, NodeInterface $node, array $defaults, array $test_values): void {
    $message = $test_values['message'];
    unset($test_values['message']);

    /** @var \Drupal\eca_content\Plugin\ECA\Condition\EntityFieldValue $condition */
    $condition = $condition_manager->createInstance('eca_entity_field_value', $test_values + $defaults);
    $condition->setContextValue('entity', $node);
    $this->assertTrue($condition->evaluate(), $message);
  }

  /**
   * Tests single string field comparison.
   */
  public function testNodeTitle(): void {
    /** @var \Drupal\eca\PluginManager\Condition $condition_manager */
    $condition_manager = \Drupal::service('plugin.manager.eca.condition');

    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => 'article',
      'uid' => 1,
      'title' => 'First article',
    ]);
    $node->save();

    // Configure default settings for condition.
    $defaults = [
      'field_name' => 'title',
      'type' => StringComparisonBase::COMPARE_TYPE_VALUE,
      'case' => Conditions::OPTION_NO,
      'negate' => Conditions::OPTION_NO,
    ];
    // Configure test values.
    $tests = [
      [
        'expected_value' => 'First article',
        'operator' => StringComparisonBase::COMPARE_EQUALS,
        'message' => 'Title equals expected value.',
      ],
      [
        'expected_value' => 'First',
        'operator' => StringComparisonBase::COMPARE_BEGINS_WITH,
        'message' => 'Title begins with expected value.',
      ],
      [
        'expected_value' => 'article',
        'operator' => StringComparisonBase::COMPARE_ENDS_WITH,
        'message' => 'Title ends with expected value.',
      ],
      [
        'expected_value' => 't a',
        'operator' => StringComparisonBase::COMPARE_CONTAINS,
        'message' => 'Title contains expected value.',
      ],
      [
        'expected_value' => 'An article',
        'operator' => StringComparisonBase::COMPARE_GREATERTHAN,
        'message' => 'Title is greater than expected value.',
      ],
      [
        'expected_value' => 'Second article',
        'operator' => StringComparisonBase::COMPARE_LESSTHAN,
        'message' => 'Title is less than expected value.',
      ],
      [
        'expected_value' => 'First article',
        'operator' => StringComparisonBase::COMPARE_ATMOST,
        'message' => 'Title is at most the equal expected value.',
      ],
      [
        'expected_value' => 'Second article',
        'operator' => StringComparisonBase::COMPARE_ATMOST,
        'message' => 'Title is at most expected value.',
      ],
      [
        'expected_value' => 'First article',
        'operator' => StringComparisonBase::COMPARE_ATLEAST,
        'message' => 'Title is at least the equal expected value.',
      ],
      [
        'expected_value' => 'An article',
        'operator' => StringComparisonBase::COMPARE_ATLEAST,
        'message' => 'Title is at least expected value.',
      ],
    ];

    // Test all the combinations.
    foreach ($tests as $test) {
      $this->evaluate($condition_manager, $node, $defaults, $test);
    }
  }

}
