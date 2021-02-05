<?php

namespace Drupal\Tests\subgroup\Kernel;

use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Tests the safety measures regarding group deletion.
 *
 * @group subgroup
 */
class GroupDeleteTest extends SubgroupKernelTestBase {

  /**
   * The subgroup handler to use in testing.
   *
   * @var \Drupal\subgroup\Entity\SubgroupHandlerInterface
   */
  protected $subgroupHandler;

  /**
   * The group storage to use in testing.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->subgroupHandler = $this->entityTypeManager->getHandler('group', 'subgroup');
    $this->storage = $this->entityTypeManager->getStorage('group');

    // Set up two group types to form a tree.
    $foo = $this->createGroupType(['id' => 'foo']);
    $bar = $this->createGroupType(['id' => 'bar']);

    // Grant delete access for members.
    $foo->getMemberRole()->grantPermission('delete group')->save();
    $bar->getMemberRole()->grantPermission('delete group')->save();

    /** @var \Drupal\subgroup\Entity\GroupTypeSubgroupHandler $group_type_handler */
    $group_type_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $group_type_handler->initTree($foo);
    $group_type_handler->addLeaf($foo, $bar);
  }

  /**
   * Tests whether regular delete access still works.
   */
  public function testRegularDeleteAccess() {
    $group_type = $this->createGroupType();
    $group_type->getMemberRole()->grantPermission('delete group')->save();
    $group = $this->createGroup(['type' => $group_type->id()]);
    $this->assertTrue($group->access('delete'), 'Group can be deleted just fine.');
  }

  /**
   * Tests delete access for a group that could be a leaf, but isn't.
   */
  public function testNoLeafDeleteAccess() {
    $group = $this->createGroup(['type' => 'foo']);
    $this->assertTrue($group->access('delete'), 'Group can be deleted just fine.');
  }

  /**
   * Tests delete access for a group leaf without descendants.
   */
  public function testLeafWithoutDescendantsDeleteAccess() {
    $group = $this->createGroup(['type' => 'foo']);
    $this->subgroupHandler->initTree($group);
    $this->assertTrue($group->access('delete'), 'Group can be deleted just fine.');
  }

  /**
   * Tests delete access for a group leaf without descendants.
   */
  public function testLeafWithDescendantsDeleteAccess() {
    $parent = $this->createGroup(['type' => 'foo']);
    $child = $this->createGroup(['type' => 'bar']);
    $this->subgroupHandler->initTree($parent);
    $this->subgroupHandler->addLeaf($parent, $child);

    $access = $parent->access('delete', NULL, TRUE);
    $this->assertInstanceOf(AccessResultForbidden::class, $access, 'Group delete access check returned an AccessResultForbidden.');
    /** @var \Drupal\Core\Access\AccessResultForbidden $access */
    $this->assertEquals('Cannot delete a leaf that still has descendants.', $access->getReason());
  }

  /**
   * Tests whether regular deletes still work.
   */
  public function testRegularDelete() {
    $group = $this->createGroup(['type' => $this->createGroupType()->id()]);
    $group->delete();
    $this->assertNull($this->storage->load($group->id()), 'Group was deleted just fine.');
  }

  /**
   * Tests the deletion of a group that could be a leaf, but isn't.
   */
  public function testNoLeafDelete() {
    $group = $this->createGroup(['type' => 'foo']);
    $group->delete();
    $this->assertNull($this->storage->load($group->id()), 'Group was deleted just fine.');
  }

  /**
   * Tests the deletion of a group leaf without descendants.
   */
  public function testLeafWithoutDescendantsDelete() {
    $group = $this->createGroup(['type' => 'foo']);
    $this->subgroupHandler->initTree($group);
    $group->delete();
    $this->assertNull($this->storage->load($group->id()), 'Group was deleted just fine.');
  }

  /**
   * Tests the deletion of a group leaf without descendants.
   */
  public function testLeafWithDescendantsDelete() {
    $parent = $this->createGroup(['type' => 'foo']);
    $child = $this->createGroup(['type' => 'bar']);
    $this->subgroupHandler->initTree($parent);
    $this->subgroupHandler->addLeaf($parent, $child);

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Cannot delete a leaf that still has descendants.');
    $parent->delete();
  }

}
