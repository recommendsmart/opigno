<?php

namespace Drupal\Tests\group_flex_content\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\group\Functional\GroupBrowserTestBase;

/**
 * Tests the behavior of the group flexible content.
 *
 * @group group
 */
class GroupFlexContentTest extends GroupBrowserTestBase {

  use StringTranslationTrait;

  /**
   * Set strict config schema.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'group',
    'group_test_config',
    'group_flex',
    'group_permissions',
    'group_flex_content',
    'group_flex_content_test',
  ];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group we will use to test methods on.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * The normal user we will use.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account;

  /**
   * Gets the global (site) permissions for the group creator.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getGlobalPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'access group overview',
      'create default group',
      'create other group',
      'administer group',
      'bypass group access',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->account = $this->createUser($this->getGlobalPermissions());
    $this->group = $this->createGroup(['uid' => $this->account->id()]);
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Add the public content type.
    $this->createContentType(['type' => 'public']);
    // Add the private content type.
    $this->createContentType(['type' => 'private']);
    // Add the flexible content type.
    $this->createContentType(['type' => 'flexible']);
  }

  /**
   * Tests group flex content type configuration.
   */
  public function testGroupFlexContentType(): void {
    $this->drupalLogin($this->account);

    // Now change the settings to enabled and public.
    $this->drupalGet('/admin/group/types/manage/default');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('group_flex_enabler', TRUE);
    $page->selectFieldOption('group_type_visibility', GROUP_FLEX_TYPE_VIS_PRIVATE);
    $this->submitForm([], 'Save group type');
    $this->assertSession()->statusCodeEquals(200);

    // Now check the public plugin install page.
    $this->drupalGet('/admin/group/content/install/default/group_node%3Apublic');
    $this->assertSession()->fieldDisabled('edit-group-content-visibility-flexible');

    // Now change the group visibility to public.
    $this->drupalGet('/admin/group/types/manage/default');
    $page = $this->getSession()->getPage();
    $page->selectFieldOption('group_type_visibility', GROUP_FLEX_TYPE_VIS_PUBLIC);
    $this->submitForm([], 'Save group type');
    $this->assertSession()->statusCodeEquals(200);

    // Now install the public plugin.
    $this->drupalGet('/admin/group/content/install/default/group_node%3Apublic');
    $this->assertSession()->fieldEnabled('edit-group-content-visibility-flexible');
    $page->selectFieldOption('group_content_visibility', 'outsider');
    $this->submitForm([], 'Install plugin');
    $this->assertSession()->statusCodeEquals(200);

    // Now install the private plugin.
    $this->drupalGet('/admin/group/content/install/default/group_node%3Aprivate');
    $this->assertSession()->fieldEnabled('edit-group-content-visibility-flexible');
    $page->selectFieldOption('group_content_visibility', 'member');
    $this->submitForm([], 'Install plugin');
    $this->assertSession()->statusCodeEquals(200);

    // Now install the flexible plugin.
    $this->drupalGet('/admin/group/content/install/default/group_node%3Aflexible');
    $this->assertSession()->fieldEnabled('edit-group-content-visibility-flexible');
    $page->selectFieldOption('group_content_visibility', 'flexible');
    $this->submitForm([], 'Install plugin');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('/group/1/content/create/group_node%3Apublic');
    $edit = [
      'title[0][value]' => 'Public node',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->drupalGet('/group/1/content/create/group_node%3Aprivate');
    $edit = [
      'title[0][value]' => 'Private node',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('/group/1/content/create/group_node%3Aflexible');
    $edit = [
      'title[0][value]' => 'Flexible public node',
      'content_visibility' => 'outsider',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('/group/1/content/create/group_node%3Aflexible');
    $edit = [
      'title[0][value]' => 'Flexible private node',
      'content_visibility' => 'member',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Open all the pages as anonymous user.
    $this->drupalLogout();
    $this->drupalGet('/node/1');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/node/2');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/node/3');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/node/4');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/all-content');
    $this->assertSession()->pageTextNotContains('Public node');
    $this->assertSession()->pageTextNotContains('Flexible public node');
    $this->assertSession()->pageTextNotContains('Private node');
    $this->assertSession()->pageTextNotContains('Flexible private node');

    // Now open all the nodes as outsider.
    $user2 = $this->createUser(['access content']);
    $this->drupalLogin($user2);
    $this->drupalGet('/node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/node/2');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/node/3');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/node/4');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/all-content');
    $this->assertSession()->pageTextContains('Public node');
    $this->assertSession()->pageTextContains('Flexible public node');
    $this->assertSession()->pageTextNotContains('Private node');
    $this->assertSession()->pageTextNotContains('Flexible private node');

    $this->group->addMember($user2);
    $this->drupalGet('/node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/node/2');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/node/3');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/node/4');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('/all-content');
    $this->assertSession()->pageTextContains('Public node');
    $this->assertSession()->pageTextContains('Flexible public node');
    $this->assertSession()->pageTextContains('Private node');
    $this->assertSession()->pageTextContains('Flexible private node');

  }

}
