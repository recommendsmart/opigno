<?php

namespace Drupal\Tests\content_to_group\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\content_to_group\Util\ContentToGroupUtility;
use Drupal\node\Entity\Node;

class HelpersTest extends UnitTestCase {

  protected $contentToGroupUtility;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->contentToGroupUtility = new ContentToGroupUtility(\Drupal::entityTypeManager());
  }

  /**
   * Test the getGroupField function.
   */
  public function testGetGroupField() {
    // Field name: field_group
    $node_group = $this->createNodeWithGroupField("field_group");
    $this->assertEquals($this->contentToGroupUtility->getGroupField($node_group), "field_group");

    // Field name: field_country_group
    $node_country_group = $this->createNodeWithGroupField("field_country_group");
    $this->assertEquals($this->contentToGroupUtility->getGroupField($node_country_group), "field_country_group");

    // No group field
    $node = $this->createNodeWithGroupField();
    $this->assertEquals($this->contentToGroupUtility->getGroupField($node), NULL);
  }

  /**
   * Creates a new node with a group field name.
   *
   * @param string $group_field
   *   The group field in the node.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node entity.
   */
  protected function createNodeWithGroupField($group_field = NULL) {
    $settings = [
      'title'  => $this->randomMachineName(8),
      'type'  => 'article',
      'uid'  => 0,
      'description' => 'A description',
      'category' => [
        'target_id' => 1
      ],
    ];
    if ($group_field !== NULL) {
      $settings[$group_field] = [
        'target_id' => 1,
        'target_type' => 'group',
      ];
    }
    $node = Node::create($settings);
    $node->save();

    return $node;
  }

}
