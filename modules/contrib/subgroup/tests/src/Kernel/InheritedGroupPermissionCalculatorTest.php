<?php

namespace Drupal\Tests\subgroup\Kernel;

/**
 * Tests that Subgroup properly hands out group permissions.
 *
 * @group subgroup
 */
class InheritedGroupPermissionCalculatorTest extends SubgroupKernelTestBase {

  /**
   * The account to use in testing.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set up two group types to form a tree.
    $foo = $this->createGroupType(['id' => 'foo']);
    $bar = $this->createGroupType(['id' => 'bar']);

    $foo->getMemberRole()->grantPermission('view group')->save();
    $bar->getMemberRole()->grantPermission('view group')->save();

    /** @var \Drupal\subgroup\Entity\GroupTypeSubgroupHandler $group_type_handler */
    $group_type_handler = $this->entityTypeManager->getHandler('group_type', 'subgroup');
    $group_type_handler->initTree($foo);
    $group_type_handler->addLeaf($foo, $bar);

    $this->account = $this->createUser();
  }

  /**
   * Tests that regular access checks still work.
   */
  public function testRegularAccess() {
    $cat = $this->createGroupType(['id' => 'cat']);
    $dog = $this->createGroupType(['id' => 'dog']);
    $cat->getMemberRole()->grantPermission('view group')->save();
    $dog->getMemberRole()->grantPermission('view group')->save();

    $group_cat = $this->createGroup(['type' => 'cat']);
    $group_dog = $this->createGroup(['type' => 'cat']);
    $group_cat->addMember($this->account);
    $this->assertTrue($group_cat->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertFalse($group_dog->hasPermission('view group', $this->account), 'Regular permissions still apply.');
  }

  /**
   * Tests that regular access checks still work if no inheritance is set up.
   */
  public function testTreeRegularAccess() {
    $group_foo = $this->createGroup(['type' => 'foo']);
    $group_bar = $this->createGroup(['type' => 'bar']);
    $group_foo->addMember($this->account);
    $this->assertTrue($group_foo->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertFalse($group_bar->hasPermission('view group', $this->account), 'Regular permissions still apply.');
  }

  /**
   * Tests that access checks work for inherited roles.
   */
  public function testInheritedAccess() {
    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-member',
      'target' => 'bar-member',
    ]));

    $group_foo = $this->createGroup(['type' => 'foo']);
    $group_bar = $this->createGroup(['type' => 'bar']);
    $group_foo->addMember($this->account);

    /** @var \Drupal\subgroup\Entity\GroupSubgroupHandler $group_handler */
    $group_handler = $this->entityTypeManager->getHandler('group', 'subgroup');
    $group_handler->initTree($group_foo);
    $group_handler->addLeaf($group_foo, $group_bar);

    $this->assertTrue($group_foo->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_bar->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');

    $account_2 = $this->createUser();
    $group_bar->addMember($account_2);
    $this->assertFalse($group_foo->hasPermission('view group', $account_2), 'Direction of inheritance is respected.');
    $this->assertTrue($group_bar->hasPermission('view group', $account_2), 'Regular permissions still apply.');
  }

  /**
   * Tests that permissions are updated when a new inheritance is set up.
   */
  public function testNewInheritance() {
    $group_foo = $this->createGroup(['type' => 'foo']);
    $group_bar = $this->createGroup(['type' => 'bar']);
    $group_foo->addMember($this->account);

    /** @var \Drupal\subgroup\Entity\GroupSubgroupHandler $group_handler */
    $group_handler = $this->entityTypeManager->getHandler('group', 'subgroup');
    $group_handler->initTree($group_foo);
    $group_handler->addLeaf($group_foo, $group_bar);

    $this->assertTrue($group_foo->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertFalse($group_bar->hasPermission('view group', $this->account), 'Regular permissions still apply.');

    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-member',
      'target' => 'bar-member',
    ]));

    $this->assertTrue($group_foo->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_bar->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');
  }

  /**
   * Tests that permissions are updated when a member status changes.
   */
  public function testNewMember() {
    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-member',
      'target' => 'bar-member',
    ]));

    $group_foo = $this->createGroup(['type' => 'foo']);
    $group_bar = $this->createGroup(['type' => 'bar']);

    /** @var \Drupal\subgroup\Entity\GroupSubgroupHandler $group_handler */
    $group_handler = $this->entityTypeManager->getHandler('group', 'subgroup');
    $group_handler->initTree($group_foo);
    $group_handler->addLeaf($group_foo, $group_bar);

    $this->assertFalse($group_foo->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertFalse($group_bar->hasPermission('view group', $this->account), 'Regular permissions still apply.');

    $group_foo->addMember($this->account);

    $this->assertTrue($group_foo->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_bar->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');
  }

  /**
   * Tests that permissions are updated when a group leaf status changes.
   */
  public function testChangedLeaf() {
    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-member',
      'target' => 'bar-member',
    ]));

    $group_foo = $this->createGroup(['type' => 'foo']);
    $group_bar = $this->createGroup(['type' => 'bar']);
    $group_foo->addMember($this->account);

    $this->assertTrue($group_foo->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertFalse($group_bar->hasPermission('view group', $this->account), 'Regular permissions still apply.');

    /** @var \Drupal\subgroup\Entity\GroupSubgroupHandler $group_handler */
    $group_handler = $this->entityTypeManager->getHandler('group', 'subgroup');
    $group_handler->initTree($group_foo);
    $group_handler->addLeaf($group_foo, $group_bar);

    // This might seem out of place, but it's to circumvent an annoying core
    // issue where entity reference fields become stale.
    // @see https://www.drupal.org/project/drupal/issues/3154443.
    $this->entityTypeManager->getStorage('group_content')->resetCache();

    $this->assertTrue($group_foo->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_bar->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');
  }

  /**
   * Tests that permissions are updated when a new leaf is added to the tree.
   */
  public function testNewLeaf() {
    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-member',
      'target' => 'bar-member',
    ]));

    $group_foo = $this->createGroup(['type' => 'foo']);
    $group_bar = $this->createGroup(['type' => 'bar']);
    $group_foo->addMember($this->account);

    /** @var \Drupal\subgroup\Entity\GroupSubgroupHandler $group_handler */
    $group_handler = $this->entityTypeManager->getHandler('group', 'subgroup');
    $group_handler->initTree($group_foo);

    $this->assertTrue($group_foo->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertFalse($group_bar->hasPermission('view group', $this->account), 'Regular permissions still apply.');

    $group_handler->addLeaf($group_foo, $group_bar);

    $this->assertTrue($group_foo->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_bar->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');
  }

  /**
   * Tests that permissions are updated when a new role is assigned.
   */
  public function testNewMemberRole() {
    $storage = $this->entityTypeManager->getStorage('group_role');
    $storage->save($storage->create([
      'id' => 'foo-viewer',
      'label' => 'Viewer',
      'audience' => 'member',
      'group_type' => 'foo',
    ])->grantPermission('view group'));

    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-viewer',
      'target' => 'bar-member',
    ]));

    $group_foo = $this->createGroup(['type' => 'foo']);
    $group_bar = $this->createGroup(['type' => 'bar']);
    $group_foo->addMember($this->account);

    /** @var \Drupal\subgroup\Entity\GroupSubgroupHandler $group_handler */
    $group_handler = $this->entityTypeManager->getHandler('group', 'subgroup');
    $group_handler->initTree($group_foo);
    $group_handler->addLeaf($group_foo, $group_bar);

    $this->assertTrue($group_foo->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertFalse($group_bar->hasPermission('view group', $this->account), 'Regular permissions still apply.');

    $membership = $group_foo->getMember($this->account)->getGroupContent();
    $membership->group_roles[] = 'foo-viewer';
    $membership->save();

    $this->assertTrue($group_foo->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_bar->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');
  }

  /**
   * Tests that permissions are updated when a role is changed.
   */
  public function testChangedRolePermissions() {
    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-member',
      'target' => 'bar-member',
    ]));

    $group_foo = $this->createGroup(['type' => 'foo']);
    $group_bar = $this->createGroup(['type' => 'bar']);
    $group_foo->addMember($this->account);

    /** @var \Drupal\subgroup\Entity\GroupSubgroupHandler $group_handler */
    $group_handler = $this->entityTypeManager->getHandler('group', 'subgroup');
    $group_handler->initTree($group_foo);
    $group_handler->addLeaf($group_foo, $group_bar);

    $this->assertTrue($group_foo->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_bar->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');

    $storage = $this->entityTypeManager->getStorage('group_role');
    $storage->save($storage->load('bar-member')->revokePermission('view group'));

    $this->assertTrue($group_foo->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertFalse($group_bar->hasPermission('view group', $this->account), 'Account inherited member role from parent group, but permission was revoked.');
  }

  /**
   * Tests that circular references are not followed and thus fine.
   */
  public function testCircularReference() {
    $storage = $this->entityTypeManager->getStorage('subgroup_role_inheritance');
    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'foo-member',
      'target' => 'bar-member',
    ]));

    $storage->save($storage->create([
      'id' => $this->randomMachineName(),
      'source' => 'bar-member',
      'target' => 'foo-member',
    ]));

    $group_foo = $this->createGroup(['type' => 'foo']);
    $group_bar = $this->createGroup(['type' => 'bar']);
    $group_foo->addMember($this->account);

    /** @var \Drupal\subgroup\Entity\GroupSubgroupHandler $group_handler */
    $group_handler = $this->entityTypeManager->getHandler('group', 'subgroup');
    $group_handler->initTree($group_foo);
    $group_handler->addLeaf($group_foo, $group_bar);

    $this->assertTrue($group_foo->hasPermission('view group', $this->account), 'Regular permissions still apply.');
    $this->assertTrue($group_bar->hasPermission('view group', $this->account), 'Account inherited member role from parent group.');
  }

}
