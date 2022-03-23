<?php

namespace Drupal\Tests\eca\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\RoleInterface;

/**
 * Tests for ECA-extended Token replacement behavior.
 *
 * @group eca
 */
class TokenTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'node',
    'eca',
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
   * Tests token aliases.
   */
  public function testTokenAlias() {
    // Create the Article content type with a standard body field.
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();
    node_add_body_field($node_type);

    $body = $this->randomMachineName(32);
    $summary = $this->randomMachineName(16);

    /** @var \Drupal\node\NodeInterface $article */
    $article = Node::create([
      'type' => 'article',
      'tnid' => 0,
      'uid' => 0,
      'title' => 'Token aliases are awesome!',
      'body' => [['value' => $body, 'summary' => $summary, 'format' => 'plain_text']],
    ]);
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => 'article',
      'tnid' => 0,
      'uid' => 0,
      'title' => 'But please do not replace me by an alias...',
      'body' => [['value' => $body, 'summary' => $summary, 'format' => 'plain_text']],
    ]);
    $node->save();

    /** @var \Drupal\eca\Token\TokenInterface $token_services */
    $token_services = \Drupal::service('eca.token_services');
    $token_services->addTokenData('article', $article);
    $this->assertEquals('Token aliases are awesome!', $token_services->replace('[article:title]', ['node' => $node]));
    $this->assertEquals('Token aliases are awesome! But please do not replace me by an alias...', $token_services->replace('[article:title] [node:title]', ['node' => $node]));

    $token_services->clearTokenData();
    $token_services->addTokenData('node', $node);
    $token_services->addTokenData('article', $article);
    $this->assertEquals('Token aliases are awesome! But please do not replace me by an alias...', $token_services->replace('[article:title] [node:title]'));

    $token_services->clearTokenData();
    $token_services->addTokenData('article', $article);
    $token_services->addTokenData('node', $node);
    $this->assertEquals('Token aliases are awesome! But please do not replace me by an alias...', $token_services->replace('[article:title] [node:title]'));

    $token_services->clearTokenData();
    $token_services->addTokenData('article', $article);
    $token_services->addTokenData('node', $node);
    $token_services->addTokenData('article', $article);
    $this->assertEquals('But please do not replace me by an alias...', $token_services->replace('[node:title]'));
    $this->assertEquals('Token aliases are awesome!', $token_services->replace('[node:title]', [
      'node' => $article,
    ]), 'Generate a replacement value when using a valid token type.');
    $this->assertEquals('Token aliases are awesome!', $token_services->replace('[article:title]', [
      'node' => $node,
    ]), 'Generate a replacement value using $article and not $node because it is an alias.');
    $this->assertEquals('Token aliases are awesome!', $token_services->replace('[article:title]', [
      'article' => $article,
      'node' => $node,
    ]), 'Generate a replacement value using $article and not $node because it is an alias.');

    $token_services->clearTokenData();
    $token_services->addTokenData('article', $article);
    $this->assertEquals('Token aliases are awesome!', $token_services->replace('[article:title]', [
      'article' => $article,
    ]), 'Using same data as argument and alias must not lead to infinite recursion.');
    $token_services->clearTokenData();
    $token_services->addTokenData('node', $article);
    $this->assertEquals('But please do not replace me by an alias...', $token_services->replace('[node:title]', [
      'node' => $node,
    ]), 'Using same data as argument and alias must not lead to infinite recursion.');
  }

  /**
   * Tests Token replacement of Data Transfer Objects (DTOs).
   */
  public function testDto() {
    /** @var \Drupal\eca\Plugin\DataType\DataTransferObject $dto */
    $dto = DataTransferObject::create();
    $dto->set('mystring', 'Hello!');

    $token = \Drupal::token();
    $token_data = ['dto' => $dto];

    $this->assertEquals('[dto]', $token->replace('[dto]', $token_data), "This must not replace anything, because a Token must consist of at least two parts.");
    $this->assertEquals('Hello!', $token->replace('[dto:mystring]', $token_data));

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
      'status' => 1,
      'title' => 'I am the node title.',
      'body' => [['value' => $body, 'summary' => $summary, 'format' => 'plain_text']],
    ]);
    $node->save();

    $dto->set('article', $node);
    $this->assertEquals('[dto:article:body]', $token->replace('[dto:article:body]', $token_data), "This must not replace anything, as the user has no access to view the node.");

    // Grant permissions and retry.
    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, ['access content']);
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, ['access content']);
    $this->assertEquals($token->replace('[node:title]', ['node' => $node]), $token->replace('[dto:article:title]', $token_data), "The result must be the same like what the Token replacement generates for the node directly.");
    $this->assertEquals($token->replace('[node:body]', ['node' => $node]), $token->replace('[dto:article:body]', $token_data), "The result must be the same like what the Token replacement generates for the node directly.");
    $this->assertEquals($token->replace('[node:summary]', ['node' => $node]), $token->replace('[dto:article:summary]', $token_data), "The result must be the same like what the Token replacement generates for the node directly.");
    $this->assertEquals($token->replace('[node:nonexistentfield]', ['node' => $node], ['clear' => TRUE]), $token->replace('[dto:article:nonexistentfield]', $token_data, ['clear' => TRUE]), "The result must be the same like what the Token replacement generates for the node directly.");
  }

  /**
   * Tests list operations on a DTO using the Token service.
   */
  public function testDtoList() {
    $dto = DataTransferObject::create();
    $dto->set('+', 'Hello');
    $dto->set('+', 'nice to meet you');
    $dto->set('+', 'good bye');
    $dto->set('+', 'well, not yet');
    $dto->set('+', 'maybe now?');
    $dto->set('-', NULL);
    $dto->set('+', 'hope you enjoy using ECA.');
    $dto->set('-', 'good bye');
    $dto->set('-', 'well, not yet');

    /** @var \Drupal\eca\Token\TokenInterface $token_services */
    $token_services = \Drupal::service('eca.token_services');
    $token_services->addTokenData('mydto:list', $dto);
    $this->assertTrue($token_services->hasTokenData('mydto'));
    $this->assertNotSame($dto, $token_services->getTokenData('mydto'));
    $this->assertTrue($token_services->hasTokenData('mydto:list'));
    $this->assertSame($dto, $token_services->getTokenData('mydto:list'));
    $this->assertEquals("Hello, nice to meet you, hope you enjoy using ECA.", $token_services->replace('[mydto:list]'));
    $this->assertEquals("Hello", $token_services->replace('[mydto:list:0]'));
    $this->assertEquals("hope you enjoy using ECA.", $token_services->replace('[mydto:list:2]'));
  }

  /**
   * Tests usages of data attached to the Token service.
   */
  public function testTokenData() {
    /** @var \Drupal\eca\Token\TokenInterface $token_services */
    $token_services = \Drupal::service('eca.token_services');

    // Create the Article content type with a standard body field.
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();
    node_add_body_field($node_type);

    $title1 = $this->randomMachineName(16);
    $body1 = $this->randomMachineName(32);
    $summary1 = $this->randomMachineName(16);

    /** @var \Drupal\node\NodeInterface $node */
    $node1 = Node::create([
      'type' => 'article',
      'tnid' => 0,
      'uid' => 0,
      'status' => 1,
      'title' => $title1,
      'body' => [['value' => $body1, 'summary' => $summary1, 'format' => 'plain_text']],
    ]);
    $node1->save();

    $title2 = $this->randomMachineName(16);
    $body2 = $this->randomMachineName(32);
    $summary2 = $this->randomMachineName(16);

    /** @var \Drupal\node\NodeInterface $node */
    $node2 = Node::create([
      'type' => 'article',
      'tnid' => 0,
      'uid' => 0,
      'status' => 1,
      'title' => $title2,
      'body' => [['value' => $body2, 'summary' => $summary2, 'format' => 'plain_text']],
    ]);
    $node2->save();

    $title3 = $this->randomMachineName(16);
    $body3 = $this->randomMachineName(32);
    $summary3 = $this->randomMachineName(16);

    /** @var \Drupal\node\NodeInterface $node */
    $node3 = Node::create([
      'type' => 'article',
      'tnid' => 0,
      'uid' => 0,
      'status' => 1,
      'title' => $title3,
      'body' => [['value' => $body3, 'summary' => $summary3, 'format' => 'plain_text']],
    ]);
    $node3->save();

    $token_services->addTokenData('node1', $node1);
    $token_services->addTokenData('node2', $node2);
    $token_services->addTokenData('node3', $node3);
    $this->assertFalse($token_services->hasTokenData('node'));
    $this->assertTrue($token_services->hasTokenData('node2'));
    $this->assertSame($node1, $token_services->getTokenData('node1'));
    $this->assertSame($node2, $token_services->getTokenData('node2'));
    $this->assertSame($node3, $token_services->getTokenData('node3'));

    // Expect a DTO when adding nested data.
    $token_services->addTokenData('myobject:node1', $node1);
    $this->assertTrue($token_services->hasTokenData('myobject'));
    $this->assertEquals(DataTransferObject::class, get_class($token_services->getTokenData('myobject')));
    $this->assertTrue($token_services->getTokenData('myobject:node1') instanceof EntityInterface);
    $this->assertNotSame($token_services->getTokenData('myobject'), $token_services->getTokenData('myobject:node1'));

    $nodes_array = [$node1, $node2, $node3];
    $node_list = ItemList::createInstance(ListDataDefinition::create('entity'));
    $node_list->setValue($nodes_array);
    $token_services->addTokenData('myobject:nodelist', $node_list);
    $this->assertSame($node_list, $token_services->getTokenData('myobject:nodelist'));

    $token_services->addTokenData('mylist', $node_list);
    $this->assertTrue($token_services->hasTokenData('mylist'));
    $mylist = $token_services->getTokenData('mylist');
    $this->assertEquals(DataTransferObject::class, get_class($mylist));
    $nodes_found = [];
    foreach ($node_list as $i => $item) {
      $this->assertTrue(isset($mylist->$i));
      $this->assertSame($item->getValue(), $mylist->get($i)->getValue());
      $nodes_found[] = $item->getValue();
    }
    $this->assertEquals($nodes_array, $nodes_found);
    $this->assertEquals($title1, $nodes_found[0]->label());
    $this->assertEquals($title2, $nodes_found[1]->label());
    $this->assertEquals($title3, $nodes_found[2]->label());
    $this->assertEquals($body1, $nodes_found[0]->body->value);
    $this->assertEquals($body2, $nodes_found[1]->body->value);
    $this->assertEquals($body3, $nodes_found[2]->body->value);
  }

}
