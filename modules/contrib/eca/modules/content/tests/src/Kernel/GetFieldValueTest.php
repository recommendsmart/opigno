<?php

namespace Drupal\Tests\eca_content\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * Kernel tests for the "eca_get_field_value" action plugin.
 *
 * @group eca
 * @group eca_content
 */
class GetFieldValueTest extends KernelTestBase {

  /**
   * The modules.
   *
   * @var string[]
   *   The modules.
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
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
    User::create(['uid' => 0, 'name' => 'guest'])->save();
    User::create(['uid' => 1, 'name' => 'admin'])->save();

    // Create the Article content type with a standard body field.
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();
    node_add_body_field($node_type);
    // Create a multi-value text field.
    FieldStorageConfig::create([
      'field_name' => 'field_string_multi',
      'type' => 'string',
      'entity_type' => 'node',
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_string_multi',
      'label' => 'A string field having multiple values.',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();
  }

  /**
   * Tests GetFieldValue on a node.
   */
  public function testGetFieldValueNode(): void {
    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = \Drupal::service('plugin.manager.action');
    /** @var \Drupal\eca\Token\TokenInterface $token_services */
    $token_services = \Drupal::service('eca.token_services');
    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');

    $string = $this->randomMachineName(32);
    $text = $this->randomMachineName(32);
    $summary = $this->randomMachineName(16);
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => 'article',
      'uid' => 1,
      'title' => '123',
      'body' => ['summary' => $summary, 'value' => $text],
      'field_string_multi' => [$string, $string . '2', $string . '3'],
    ]);
    $node->save();

    /** @var \Drupal\eca_content\Plugin\Action\GetFieldValue $action */
    $action = $action_manager->createInstance('eca_get_field_value', [
      'token_name' => 'my_custom_token:bodyvalue',
      'field_name' => 'body.value',
    ]);
    $this->assertFalse($action->access($node), 'User without permissions must not have access.');

    // Now switching to priviledged user.
    $account_switcher->switchTo(User::load(1));
    /** @var \Drupal\eca_content\Plugin\Action\GetFieldValue $action */
    $action = $action_manager->createInstance('eca_get_field_value', [
      'token_name' => 'my_custom_token:bodyvalue',
      'field_name' => 'body.value',
    ]);
    $this->assertTrue($action->access($node), 'User with permissions must have access.');
    $action->execute($node);
    $this->assertEquals($text, $token_services->replaceClear('[my_custom_token:bodyvalue]'));
    $this->assertEquals('', $token_services->replaceClear('[my_custom_token:body]'));

    /** @var \Drupal\eca_content\Plugin\Action\GetFieldValue $action */
    $action = $action_manager->createInstance('eca_get_field_value', [
      'token_name' => 'my_custom_token:body',
      'field_name' => 'body',
    ]);
    $action->execute($node);
    $this->assertEquals($text, $token_services->replaceClear('[my_custom_token:bodyvalue]'));
    $this->assertEquals($text, $token_services->replaceClear('[my_custom_token:body]'));

    /** @var \Drupal\eca_content\Plugin\Action\GetFieldValue $action */
    $action = $action_manager->createInstance('eca_get_field_value', [
      'token_name' => 'my_custom_token:body_summary',
      'field_name' => 'body.0.summary',
    ]);
    $action->execute($node);
    $this->assertEquals($summary, $token_services->replaceClear('[my_custom_token:body_summary]'));

    /** @var \Drupal\eca_content\Plugin\Action\GetFieldValue $action */
    $action = $action_manager->createInstance('eca_get_field_value', [
      'token_name' => 'another_one:string_2',
      'field_name' => 'field_string_multi:1:value',
    ]);
    $action->execute($node);
    $this->assertEquals($string . '2', $token_services->replaceClear('[another_one:string_2]'));

    /** @var \Drupal\eca_content\Plugin\Action\GetFieldValue $action */
    $action = $action_manager->createInstance('eca_get_field_value', [
      'token_name' => 'a_root_token',
      'field_name' => 'field_string_multi.value',
    ]);
    $action->execute($node);
    $this->assertEquals($string, $token_services->replaceClear('[a_root_token]'), "By default the first item will be used.");

    $account_switcher->switchBack();
  }

}
