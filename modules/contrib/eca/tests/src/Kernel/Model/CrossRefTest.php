<?php

namespace Drupal\Tests\eca\Kernel\Model;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Model test for cross references.
 *
 * @group eca_model
 */
class CrossRefTest extends Base {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'eca_base',
    'eca_content',
    'eca_test_model_cross_ref',
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
   * Tests cross references.
   */
  public function testCrossReference(): void {

    // Create a first node which doesn't reference any other node yet.
    $title1 = $this->randomMachineName();
    /** @var \Drupal\node\NodeInterface $node1 */
    $node1 = Node::create([
      'type' => 'type_1',
      'tnid' => 0,
      'uid' => 1,
      'title' => $title1,
    ]);
    $node1->save();
    $this->assertNoMessages();
    $this->assertNoError();
    $this->assertEmpty($node1->get('field_other_node')
      ->getValue(), 'Field "Other Node" should be empty.');

    // Create a second node which refers to the first node. This should then
    // also update the first node, so that both nodes refer to each other.
    $title2 = $this->randomMachineName();
    /** @var \Drupal\node\NodeInterface $node2 */
    $node2 = Node::create([
      'type' => 'type_2',
      'tnid' => 0,
      'uid' => 1,
      'title' => $title2,
      'field_other_node' => $node1->id(),
    ]);
    $node2->save();
    $this->assertNodesAndMessages($node1, $node2, $title1, $title2, [
      "Node $title2 references $title1",
      "Node $title1 got updated",
    ]);

    // Save both nodes again and make sure, that nothing will change.
    $node1->save();
    $node2->save();
    $this->assertNodesAndMessages($node1, $node2, $title1, $title2, [
      "Node $title1 got updated",
      "Node $title2 got updated",
    ]);

    // Change the title of both nodes and save them again, to verify that
    // saving the nodes works, but the references don't get changed and no
    // recursion is happening.
    $title1 = $this->randomMachineName();
    $title2 = $this->randomMachineName();
    $node1->setTitle($title1)->save();
    $node2->setTitle($title2)->save();
    $this->assertNodesAndMessages($node1, $node2, $title1, $title2, [
      "Node $title1 got updated",
      "Node $title2 got updated",
    ]);
  }

  /**
   * @param \Drupal\node\NodeInterface $node1
   * @param \Drupal\node\NodeInterface $node2
   * @param string $title1
   * @param string $title2
   * @param array $messages
   */
  private function assertNodesAndMessages(NodeInterface &$node1, NodeInterface &$node2, string $title1, string $title2, array $messages): void {
    $node1 = Node::load($node1->id());
    $node2 = Node::load($node2->id());

    if (!empty($messages)) {
      $this->assertStatusMessages($messages);
    }
    $this->assertNoMessages();
    $this->assertNoError();

    $this->assertNotEmpty($node2->get('field_other_node')->getValue(), 'Field "Other Node" should not be empty.');
    $this->assertEquals($node1->id(), $node2->get('field_other_node')->getValue()[0]['target_id'], 'Field "Other Node" should matches the first created node.');

    $this->assertNotEmpty($node1->get('field_other_node')->getValue(), 'Field "Other Node" of first created node should not be empty.');
    $this->assertEquals($node2->id(), $node1->get('field_other_node')->getValue()[0]['target_id'], 'Cross referenmce should have been updated.');

    $this->assertEquals($node1->label(), $title1, 'Title of first created node is incorrect.');
    $this->assertEquals($node2->label(), $title2, 'Title of first created node is incorrect.');
  }

}
