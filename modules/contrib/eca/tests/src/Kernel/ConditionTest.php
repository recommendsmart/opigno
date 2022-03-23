<?php

namespace Drupal\Tests\eca\Kernel;

use Drupal\eca\Service\Conditions;
use Drupal\eca_base\Plugin\ECA\Condition\ScalarComparison;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests for ECA condition plugins.
 *
 * @group eca
 */
class ConditionTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'node',
    'eca',
    'eca_base',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(static::$modules);
  }

  /**
   * Tests scalar comparison.
   */
  public function testScalarComparison() {
    // Create the Article content type with a standard body field.
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();
    node_add_body_field($node_type);

    $body = $this->randomMachineName(32);
    $summary = $this->randomMachineName(16);

    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => 'article',
      'tnid' => 0,
      'uid' => 0,
      'title' => '123',
      'body' => [['value' => $body, 'summary' => $summary, 'format' => 'plain_text']],
    ]);
    $node->save();

    /** @var \Drupal\eca\Token\TokenInterface $token_services */
    $token_services = \Drupal::service('eca.token_services');
    $token_services->addTokenData('node', $node);
    /** @var \Drupal\Component\Plugin\PluginManagerInterface $condition_plugin_manager */
    $condition_plugin_manager = \Drupal::service('plugin.manager.eca.condition');
    /** @var \Drupal\eca_base\Plugin\ECA\Condition\ScalarComparison $plugin */
    $plugin = $condition_plugin_manager->createInstance('eca_scalar', [
      'left' => '[node:title]',
      'right' => '123',
      'type' => ScalarComparison::COMPARE_TYPE_VALUE,
      'operator' => ScalarComparison::COMPARE_EQUALS,
      'case' => Conditions::OPTION_YES,
    ]);
    $this->assertTrue($plugin->evaluate());

    /** @var \Drupal\eca_base\Plugin\ECA\Condition\ScalarComparison $plugin */
    $plugin = $condition_plugin_manager->createInstance('eca_scalar', [
      'left' => '[node:title]',
      'right' => '124',
      'type' => ScalarComparison::COMPARE_TYPE_VALUE,
      'operator' => ScalarComparison::COMPARE_EQUALS,
      'case' => Conditions::OPTION_YES,
    ]);
    $this->assertFalse($plugin->evaluate());

    /** @var \Drupal\eca_base\Plugin\ECA\Condition\ScalarComparison $plugin */
    $plugin = $condition_plugin_manager->createInstance('eca_scalar', [
      'left' => '[node:title]',
      'right' => 'aaa',
      'type' => ScalarComparison::COMPARE_TYPE_COUNT,
      'operator' => ScalarComparison::COMPARE_EQUALS,
      'case' => Conditions::OPTION_YES,
    ]);
    $this->assertTrue($plugin->evaluate());

    /** @var \Drupal\eca_base\Plugin\ECA\Condition\ScalarComparison $plugin */
    $plugin = $condition_plugin_manager->createInstance('eca_scalar', [
      'left' => '[node:title]',
      'right' => 'aaaa',
      'type' => ScalarComparison::COMPARE_TYPE_COUNT,
      'operator' => ScalarComparison::COMPARE_EQUALS,
      'case' => Conditions::OPTION_YES,
    ]);
    $this->assertFalse($plugin->evaluate());

    /** @var \Drupal\eca_base\Plugin\ECA\Condition\ScalarComparison $plugin */
    $plugin = $condition_plugin_manager->createInstance('eca_scalar', [
      'left' => '100',
      'right' => '99',
      'type' => ScalarComparison::COMPARE_TYPE_NUMERIC,
      'operator' => ScalarComparison::COMPARE_GREATERTHAN,
      'case' => Conditions::OPTION_YES,
    ]);
    $this->assertTrue($plugin->evaluate());

    /** @var \Drupal\eca_base\Plugin\ECA\Condition\ScalarComparison $plugin */
    $plugin = $condition_plugin_manager->createInstance('eca_scalar', [
      'left' => '99',
      'right' => '100',
      'type' => ScalarComparison::COMPARE_TYPE_NUMERIC,
      'operator' => ScalarComparison::COMPARE_GREATERTHAN,
      'case' => Conditions::OPTION_YES,
    ]);
    $this->assertFalse($plugin->evaluate());

    /** @var \Drupal\eca_base\Plugin\ECA\Condition\ScalarComparison $plugin */
    $plugin = $condition_plugin_manager->createInstance('eca_scalar', [
      'left' => '100',
      'right' => 'DrUpAl!',
      'type' => ScalarComparison::COMPARE_TYPE_NUMERIC,
      'operator' => ScalarComparison::COMPARE_GREATERTHAN,
      'case' => Conditions::OPTION_YES,
    ]);
    $this->assertFalse($plugin->evaluate());
  }

}
