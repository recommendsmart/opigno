<?php

namespace Drupal\Tests\crm_core_activity\Functional;

use Drupal\crm_core_activity\Entity\ActivityType;
use Drupal\crm_core_contact\Entity\Individual;
use Drupal\crm_core_contact\Entity\IndividualType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the UI for Activity CRUD operations.
 *
 * @group crm_core
 */
class ActivityUiTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'crm_core_contact',
    'crm_core_activity',
    'crm_core_tests',
    'block',
    'entity',
    'views_ui',
    'datetime',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Place local actions blocks.
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');

    IndividualType::create([
      'name' => 'Customer',
      'type' => 'customer',
      'description' => 'A single customer.',
      'primary_fields' => [],
    ])->save();

    ActivityType::create([
      'type' => 'meeting',
      'name' => 'Meeting',
      'description' => 'A meeting between 2 or more contacts.',
    ])->save();

    ActivityType::create([
      'type' => 'phone_call',
      'name' => 'Phone call',
      'description' => 'A phone call between 2 or more contacts.',
    ])->save();

    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Test basic UI operations with Activities.
   *
   * Create an individual.
   * Add activity of every type to individual.
   * Assert activities listed on Activities tab listing page.
   * Edit every activity. Assert activities changed from listing page.
   * Delete every activity. Assert they disappeared from listing page.
   */
  public function testActivityOperations() {
    // Create and login user. User should be able to create individuals and
    // activities.
    $user = $this->drupalCreateUser([
      'administer crm_core_individual entities',
      'view any crm_core_individual entity',
      'administer crm_core_activity entities',
      'administer activity types',
      'view any crm_core_activity entity',
    ]);
    $this->drupalLogin($user);

    // Create customer individual.
    $individual = Individual::create([
      'name' => [
        'given' => 'John',
        'family' => 'Smith',
      ],
      'type' => 'customer',
    ]);
    $individual->save();

    $this->drupalGet('crm-core/activity');
    $this->assertSession()->pageTextContains('There are no activities available.');

    $this->assertSession()->linkExists('Add an activity');
    $this->drupalGet('crm-core/activity/add');

    $this->assertSession()->linkExists('Meeting');
    $this->assertSession()->linkExists('Phone call');

    // Create Meeting activity. Ensure it is listed.
    $this->drupalGet('crm-core/activity/add/meeting');
    $this->assertSession()->pageTextContains('Format: ' . date('Y-m-d'));
    $this->assertSession()->pageTextContains('Entity type');
    $meeting_activity = [
      'title[0][value]' => 'Pellentesque',
      'activity_date[0][value][date]' => $this->randomDate(),
      'activity_date[0][value][time]' => $this->randomTime(),
      'activity_notes[0][value]' => $this->randomString(),
      'activity_participants[0][target_type]' => $individual->getEntityTypeId(),
      'activity_participants[0][target_id]' => $individual->label() . ' (' . $individual->id() . ')',
    ];

    // Assert the breadcrumb.
    $this->assertSession()->linkExists('Home');
    $this->assertSession()->linkExists('CRM');
    $this->assertSession()->linkExists('Activities');

    $this->drupalPostForm(NULL, $meeting_activity, 'Save Activity');
    $this->assertSession()->pageTextContains('Activity Pellentesque created.');

    $activities = \Drupal::entityTypeManager()
      ->getStorage('crm_core_activity')
      ->loadByProperties(['title' => 'Pellentesque']);
    $meeting_activity = current($activities);

    // Create another user.
    $new_user = $this->drupalCreateUser();

    // Test EntityOwnerTrait functions on meeting activity.
    $this->assertEqual($meeting_activity->getOwnerId(), $user->id());
    $this->assertEqual($meeting_activity->getOwner()->id(), $user->id());
    $meeting_activity->setOwner($new_user);
    $this->assertEqual($meeting_activity->getOwnerId(), $new_user->id());
    $this->assertEqual($meeting_activity->getOwner()->id(), $new_user->id());
    $meeting_activity->setOwnerId($user->id());
    $this->assertEqual($meeting_activity->getOwnerId(), $user->id());
    $this->assertEqual($meeting_activity->getOwner()->id(), $user->id());

    // Test Activity::hasParticipant() method.
    $this->assertTrue(
      $meeting_activity->hasParticipant($individual),
      'Meeting activity has participant ' . $individual->label()
    );
    $new_individual = Individual::create([
      'name' => [
        'given' => 'John',
        'family' => 'Doe',
      ],
      'type' => 'customer',
    ]);
    $new_individual->save();
    $this->assertFalse($meeting_activity->hasParticipant($new_individual),
      'Meeting activity does not have ' . $new_individual->label()
    );

    // Create Meeting activity. Ensure it it listed.
    $phonecall_activity = [
      'title[0][value]' => 'Mollis',
      'activity_date[0][value][date]' => $this->randomDate(),
      'activity_date[0][value][time]' => $this->randomTime(),
      'activity_notes[0][value]' => $this->randomString(),
      'activity_participants[0][target_type]' => $individual->getEntityTypeId(),
      'activity_participants[0][target_id]' => $individual->label() . ' (' . $individual->id() . ')',
    ];
    $this->drupalPostForm('crm-core/activity/add/phone_call', $phonecall_activity, 'Save Activity');
    $this->assertSession()->pageTextContains('Activity Mollis created.');

    /** @var \Drupal\crm_core_activity\Entity\Activity $phonecall_activity_db */
    $phonecall_activity_db = \Drupal::entityTypeManager()
      ->getStorage('crm_core_activity')
      ->loadByProperties(['title' => 'Mollis']);
    $phonecall_activity_db = reset($phonecall_activity_db);
    $this->assertTrue($phonecall_activity_db->hasParticipant($individual));

    $this->drupalGet('crm-core/activity/' . $phonecall_activity_db->id() . '/edit');
    // Update activity and assert its title changed on the list.
    $meeting_activity = [
      'title[0][value]' => 'Vestibulum',
      'activity_notes[0][value]' => 'Pellentesque egestas neque sit',
    ];
    $this->drupalPostForm(NULL, $meeting_activity, 'Save Activity');
    // Activity updated.
    $this->assertSession()->pageTextContains('Vestibulum');
    $this->drupalGet('crm-core/activity');
    $this->assertSession()->linkExists('Vestibulum');

    // Assert all views headers are available.
    $this->assertSession()->linkExists('Activity Date');
    $this->assertSession()->linkExists('Title');
    $this->assertSession()->linkExists('Activity Type');
    $this->assertSession()->pageTextContains('Operations');
    $this->assertSession()->pageTextContains('Activity preview');

    $elements = $this->xpath('//form[@class="views-exposed-form"]/div/div/label[text()="Title"]');
    // Title is an exposed filter.
    $this->assertCount(1, $elements);

    $elements = $this->xpath('//form[@class="views-exposed-form"]/div/div/label[text()="Type"]');
    // Activity type is an exposed filter.
    $this->assertCount(1, $elements);

    $activities = \Drupal::entityTypeManager()
      ->getStorage('crm_core_activity')
      ->loadByProperties(['title' => 'Vestibulum']);
    $activity = current($activities);

    $this->assertRaw('crm-core/activity/' . $activity->id() . '/edit');
    $this->assertRaw('crm-core/activity/' . $activity->id() . '/delete');
    $date = $activity->get('activity_date')->date;
    $this->container->get('date.formatter')->format($date->getTimeStamp(), 'medium');
    $this->assertSession()->pageTextContains($this->container->get('date.formatter')->format($date->getTimeStamp(), 'medium'));

    // Get test view page and check fields data.
    $this->drupalGet('activity-view-data');
    $this->assertSession()->pageTextContains('Vestibulum');
    $this->assertSession()->pageTextContains('Pellentesque egestas neque sit');

    // Test that empty activity_participants field is not allowed.
    $empty_participant = [
      'activity_participants[0][target_id]' => '',
    ];
    $this->drupalPostForm('crm-core/activity/1/edit', $empty_participant, 'Save Activity');
    $this->assertSession()->pageTextContains('Label field is required.');

    // Update phone call activity and assert its title changed on the list.
    $phonecall_activity = [
      'title[0][value]' => 'Commodo',
    ];
    $this->drupalPostForm('crm-core/activity/2/edit', $phonecall_activity, 'Save Activity');
    $this->assertSession()->pageTextContains('Commodo');
    $this->drupalGet('crm-core/activity');
    $this->assertSession()->linkExists('Commodo', 0);

    // Delete Meeting activity.
    $this->drupalPostForm('crm-core/activity/1/delete', [], 'Delete');
    $this->assertSession()->pageTextContains('Meeting Pellentesque has been deleted.');
    $this->drupalGet('crm-core/activity');
    $this->assertSession()->linkNotExists('Pellentesque');

    // Delete Phone call activity.
    $this->drupalPostForm('crm-core/activity/2/delete', [], 'Delete');
    $this->assertSession()->pageTextContains('Phone call Commodo has been deleted.');
    $this->drupalGet('crm-core/activity');
    $this->assertSession()->linkNotExists('Commodo');

    // Assert there is no activities left.
    $this->drupalGet('crm-core/activity');
    $this->assertSession()->pageTextContains('There are no activities available.');

    // Test activity type operations.
    $this->drupalGet('admin/structure/crm-core/activity-types');

    // Add new activity type.
    $this->clickLink('Add activity type');
    $new_activity_type = [
      'name' => 'New activity type',
      'type' => 'new_activity_type',
      'description' => 'New activity type description',
    ];
    $this->drupalPostForm(NULL, $new_activity_type, 'Save activity type');

    // Check that new activity type is displayed in activity types overview.
    $this->drupalGet('admin/structure/crm-core/activity-types');
    $this->assertSession()->pageTextContains($new_activity_type['name']);

    // Edit activity type.
    $this->clickLink('Edit', 1);
    $edit = [
      'name' => 'Edited activity type',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save activity type');
    $this->drupalGet('admin/structure/crm-core/activity-types');
    $this->assertSession()->pageTextContains($edit['name']);

    // Test activity type delete operation.
    $this->drupalGet('admin/structure/crm-core/activity-types');
    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], 'Delete');
    $this->assertSession()->pageTextContains('The activity type ' . $edit['name'] . ' has been deleted.');
    $this->drupalGet('admin/structure/crm-core/activity-types');
    $this->assertSession()->pageTextNotContains($edit['name']);
  }

  /**
   * Test list builder views for activity entity.
   */
  public function testListBuilder() {
    $user = $this->drupalCreateUser([
      'view any crm_core_activity entity',
      'view any crm_core_activity entity',
      'administer views',
    ]);
    $this->drupalLogin($user);

    // Delete generated activity view to get default view from list builder.
    $this->drupalGet('admin/structure/views/view/crm_core_activity_overview/delete');
    $this->drupalPostForm(NULL, [], 'Delete');
    // Check activity collection page.
    $this->drupalGet('/crm-core/activity');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Generate random Date for form element input.
   */
  protected function randomDate() {
    return \Drupal::service('date.formatter')->format(REQUEST_TIME + rand(0, 100000), 'custom', 'Y-m-d');
  }

  /**
   * Generate random Time for form element input.
   */
  protected function randomTime() {
    return \Drupal::service('date.formatter')->format(REQUEST_TIME + rand(0, 100000), 'custom', 'H:m:s');
  }

}
