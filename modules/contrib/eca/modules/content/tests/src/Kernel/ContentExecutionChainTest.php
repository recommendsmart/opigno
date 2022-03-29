<?php

namespace Drupal\Tests\eca_content\Kernel;

use Drupal\eca\Entity\Eca;
use Drupal\eca\Entity\EcaStorage;
use Drupal\eca\Service\Conditions;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * Execution chain tests using plugins of eca_content.
 *
 * @group eca
 * @group eca_content
 */
class ContentExecutionChainTest extends KernelTestBase {

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
    User::create(['uid' => 1, 'name' => 'admin'])->save();
    User::create(['uid' => 2, 'name' => 'authenticated'])->save();
    // Create the Article content type with revisioning and translation enabled.
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
      'new_revision' => TRUE,
    ]);
    $node_type->save();
    user_role_grant_permissions('authenticated', [
      'access content',
      'edit own article content',
    ]);
  }

  /**
   * Tests execution chains using plugins of eca_content.
   */
  public function testExecutionChain() {
    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');

    $published_node = Node::create([
      'type' => 'article',
      'title' => 'Published node',
      'langcode' => 'en',
      'uid' => 2,
      'status' => 1,
    ]);
    $published_node->save();
    $published_node = Node::load($published_node->id());
    $unpublished_node = Node::create([
      'type' => 'article',
      'title' => 'Unpublished node',
      'langcode' => 'en',
      'uid' => 1,
      'status' => 0,
    ]);
    $unpublished_node->save();
    $unpublished_node = Node::load($unpublished_node->id());

    // This config does the following:
    //   1. Loads the published node and sets its title
    //   2. Loads the unpublished node and sets its title
    //   3. Loads the published node again and sets its title yet again
    $eca_config_values = [
      'langcode' => 'en',
      'status' => TRUE,
      'id' => 'presaving_node_process',
      'label' => 'ECA presaving node',
      'modeller' => 'fallback',
      'version' => '1.0.0',
      'events' => [
        'event_presave' => [
          'plugin' => 'content_entity:presave',
          'label' => 'Presaving content',
          'fields' => [
            'type' => 'node article',
          ],
          'successors' => [
            ['id' => 'action_load_published', 'condition' => ''],
          ],
        ],
      ],
      'actions' => [
        'action_load_published' => [
          'plugin' => 'eca_token_load_entity',
          'label' => 'Load published node',
          'fields' => [
            'token_name' => 'mynode',
            'from' => 'id',
            'entity_type' => 'node',
            'entity_id' => (string) $published_node->id(),
            'revision_id' => '',
            'properties' => '',
            'langcode' => '_interface',
            'latest_revision' => Conditions::OPTION_NO,
            'unchanged' => Conditions::OPTION_NO,
          ],
          'successors' => [
            ['id' => 'action_set_published_title', 'condition' => ''],
          ],
        ],
        'action_set_published_title' => [
          'plugin' => 'eca_set_field_value',
          'label' => 'Set title of published node',
          'fields' => [
            'field_name' => 'title',
            'field_value' => 'Changed published TITLE for the first time!',
            'method' => 'set:clear',
            'strip_tags' => Conditions::OPTION_NO,
            'trim' => Conditions::OPTION_NO,
            'save_entity' => Conditions::OPTION_NO,
            'object' => 'mynode',
          ],
          'successors' => [
            ['id' => 'action_load_unpublished', 'condition' => ''],
          ],
        ],
        'action_load_unpublished' => [
          'plugin' => 'eca_token_load_entity',
          'label' => 'Load unpublished node',
          'fields' => [
            'token_name' => 'mynode',
            'from' => 'id',
            'entity_type' => 'node',
            'entity_id' => (string) $unpublished_node->id(),
            'revision_id' => '',
            'properties' => '',
            'langcode' => '_interface',
            'latest_revision' => Conditions::OPTION_NO,
            'unchanged' => Conditions::OPTION_NO,
          ],
          'successors' => [
            ['id' => 'action_set_unpublished_title', 'condition' => ''],
          ],
        ],
        'action_set_unpublished_title' => [
          'plugin' => 'eca_set_field_value',
          'label' => 'Set title of unpublished node',
          'fields' => [
            'field_name' => 'title',
            'field_value' => 'Changed TITLE of unpublished!',
            'method' => 'set:clear',
            'strip_tags' => Conditions::OPTION_NO,
            'trim' => Conditions::OPTION_NO,
            'save_entity' => Conditions::OPTION_NO,
            'object' => 'mynode',
          ],
          'successors' => [
            ['id' => 'action_load_published_again', 'condition' => ''],
          ],
        ],
        'action_load_published_again' => [
          'plugin' => 'eca_token_load_entity',
          'label' => 'Load published node again',
          'fields' => [
            'token_name' => 'mynode',
            'from' => 'id',
            'entity_type' => 'node',
            'entity_id' => (string) $published_node->id(),
            'revision_id' => '',
            'properties' => '',
            'langcode' => '_interface',
            'latest_revision' => Conditions::OPTION_NO,
            'unchanged' => Conditions::OPTION_NO,
          ],
          'successors' => [
            ['id' => 'action_set_published_title_again', 'condition' => ''],
          ],
        ],
        'action_set_published_title_again' => [
          'plugin' => 'eca_set_field_value',
          'label' => 'Set title of published node',
          'fields' => [
            'field_name' => 'title',
            'field_value' => 'Finally changed the published TITLE!',
            'method' => 'set:clear',
            'strip_tags' => Conditions::OPTION_NO,
            'trim' => Conditions::OPTION_NO,
            'save_entity' => Conditions::OPTION_NO,
            'object' => 'mynode',
          ],
          'successors' => [],
        ],
      ],
    ];
    $ecaConfig = Eca::create($eca_config_values);
    $ecaConfig->trustData()->save();

    // Switch to priviledged account.
    $account_switcher->switchTo(User::load(1));

    // Create another node and save it. That should trigger the created ECA
    // configuration which will set the node titles.
    $title = $this->randomMachineName(8);
    $node = Node::create([
      'type' => 'article',
      'title' => $title,
      'langcode' => 'en',
      'uid' => 2,
      'status' => 1,
    ]);
    $node->save();

    $this->assertEquals($title, $node->label(), 'Title of node being saved must remain unchanged.');
    $this->assertEquals('Finally changed the published TITLE!', $published_node->label(), 'Title of published node must have been changed by ECA configuration.');
    $this->assertEquals('Changed TITLE of unpublished!', $unpublished_node->label(), 'Title of unpublished node must have been changed by ECA configuration.');

    // End of tests with priviledged user.
    $account_switcher->switchBack();

    // The next test will execute the same configuration on a non-priviledged
    // user. That user only has update access to the published node, therefore
    // the unpublished one must not be changed by ECA.

    // Disable the ECA config first to do some value resets without executing.
    $ecaConfig->disable()->trustData()->save();
    $published_node->title->value = 'Published node';
    $published_node->save();
    $published_node = Node::load($published_node->id());
    $unpublished_node->title->value = 'Unpublished node';
    $unpublished_node->save();
    $unpublished_node = Node::load($unpublished_node->id());
    $this->assertEquals('Published node', $published_node->label(), 'Published node title must remain unchanged.');
    $this->assertEquals('Unpublished node', $unpublished_node->label(), 'Unpublished node title must remain unchanged.');
    $ecaConfig->enable()->trustData()->save();

    // Now switch to a non-priviledged account.
    $account_switcher->switchTo(User::load(2));

    // Create another node and save it. That should trigger the created ECA
    // configuration which will set the node titles.
    $title = $this->randomMachineName(8);
    $node = Node::create([
      'type' => 'article',
      'title' => $title,
      'langcode' => 'en',
      'uid' => 1,
      'status' => 1,
    ]);
    $node->save();

    $this->assertEquals($title, $node->label(), 'Title of node being saved must remain unchanged.');
    $this->assertEquals('Changed published TITLE for the first time!', $published_node->label(), 'Title of published node must have been changed by ECA configuration only for the first time, because the process chained stopped as the unpublished entity is not accessible.');
    $this->assertEquals('Unpublished node', $unpublished_node->label(), 'Unpublished node title must remain unchanged, as it is not accessible.');

    // Reset the values once more and do another test with unpriviledged user.
    $ecaConfig->disable()->trustData()->save();
    $published_node->title->value = 'Published node';
    $published_node->save();
    $published_node = Node::load($published_node->id());
    $unpublished_node->title->value = 'Unpublished node';
    $unpublished_node->save();
    $unpublished_node = Node::load($unpublished_node->id());
    $this->assertEquals('Published node', $published_node->label(), 'Published node title must remain unchanged.');
    $this->assertEquals('Unpublished node', $unpublished_node->label(), 'Unpublished node title must remain unchanged.');
    $ecaConfig->enable()->trustData()->save();

    // Delete the unpublished node, so that it's not available anymore.
    $unpublished_node->delete();
    $this->assertEquals('Published node', $published_node->label(), 'Published node title must remain unchanged.');

    $node->save();
    $this->assertEquals($title, $node->label(), 'Title of node being saved must remain unchanged.');
    $this->assertEquals('Changed published TITLE for the first time!', $published_node->label(), 'Title of published node must have been changed by ECA configuration only once, because subsequent actions tried to load an inaccessible node.');

    $account_switcher->switchBack();
  }

}
