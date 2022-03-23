<?php

namespace Drupal\Tests\eca\Kernel\Model;

use Drupal\node\Entity\Node;

/**
 * Model test for entity basics.
 *
 * @group eca_model
 */
class EntityBasicsTest extends Base {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'eca_content',
    'eca_user',
    'eca_test_model_entity_basics',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->switchUser(1);
  }

  /**
   * Tests entity basics on an article.
   */
  public function testArticle(): void {
    $title = $this->randomMachineName();
    $titleModified = 'Article from ' . self::USER_1_NAME;
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => 'article',
      'tnid' => 0,
      'uid' => 1,
      'title' => $title,
    ]);
    $node->save();

    $this->assertStatusMessages([
      "Made node $title sticky",
      "Promoted article $title to front page",
      "Updated title of article",
    ]);
    $this->assertNoMessages();
    $this->assertNoError();
    $this->assertEquals($node->label(), $titleModified, 'Title update did not produce the expected value.');

    // Update the node.
    $node->save();
    $nodeId = $node->id();

    $this->assertStatusMessages([
      "Made node $titleModified sticky",
      "Promoted article $titleModified to front page",
      "Updated title of article",
      "Node $nodeId ($titleModified) was updated and ECA recognized this.",
    ]);
    $this->assertNoMessages();
    $this->assertNoError();
    $this->assertEquals($node->label(), $titleModified, 'Title update did not produce the expected value.');
  }

  /**
   * Tests entity basics on a basic page.
   */
  public function testBasicPage(): void {
    $title = $this->randomMachineName();
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => 'page',
      'tnid' => 0,
      'uid' => 1,
      'title' => $title,
    ]);
    $node->save();

    $this->assertStatusMessages([
      "Made node $title sticky",
    ]);
    $this->assertNoMessages();
    $this->assertNoError();
    $this->assertEquals($node->label(), $title, 'Title should not have been updated.');

    // Update the node.
    $node->save();
    $nodeId = $node->id();

    $this->assertStatusMessages([
      "Made node $title sticky",
      "Node $nodeId ($title) was updated and ECA recognized this.",
    ]);
    $this->assertNoMessages();
    $this->assertNoError();
    $this->assertEquals($node->label(), $title, 'Title should not have been updated.');
  }

}
