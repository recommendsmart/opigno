<?php

namespace Drupal\Tests\crm_core_contact\Functional;

use Drupal\crm_core_contact\Entity\Individual;
use Drupal\crm_core_contact\Entity\IndividualType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the UI for Individual CRUD operations.
 *
 * @group crm_core
 */
class IndividualUiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
    'views_ui',
    'options',
    'datetime',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    IndividualType::create([
      'name' => 'Customer',
      'type' => 'customer',
      'description' => 'A single customer.',
      'primary_fields' => [],
    ])->save();

    // Place local actions and local task blocks.
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the individual operations.
   *
   * User with permissions 'administer crm_core_individual entities'
   * should be able to create/edit/delete individuals of any individual type.
   */
  public function testIndividualOperations(): void {
    $this->drupalGet('crm-core');
    $this->assertSession()->statusCodeEquals(403);

    $user = $this->drupalCreateUser([
      'view any crm_core_individual entity',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('crm-core');
    $this->assertSession()->linkExists('CRM Individuals');
    $this->assertSession()->linkNotExists('CRM Activities');

    $user = $this->drupalCreateUser([
      'view any crm_core_activity entity',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('crm-core');
    $this->assertSession()->linkNotExists('CRM Individuals');
    $this->assertSession()->linkExists('CRM Activities');

    // User has no permission to create Customer individuals.
    $this->assertSession()->linkByHrefNotExists('crm-core/individual/add/customer');
    $this->drupalGet('crm-core/individual/add/customer');
    $this->assertSession()->statusCodeEquals(403);

    // Create user and login.
    $user = $this->drupalCreateUser([
      'delete any crm_core_individual entity of bundle customer',
      'create crm_core_individual entities of bundle customer',
      'view any crm_core_individual entity',
      'view any crm_core_activity entity',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('crm-core');

    $this->assertTitle('CRM | Drupal');

    $this->assertSession()->linkExists('CRM Activities');
    $this->assertSession()->linkExists('CRM Individuals');
    $this->clickLink('CRM Individuals');
    // There should be no individuals available after fresh installation and
    // there is a link to create new individuals.
    $this->assertSession()->pageTextContains('There are no individuals available.');
    $this->assertSession()->linkExists('Add an individual');

    $this->drupalGet('crm-core/individual/add');
    $this->assertUrl('crm-core/individual/add/customer');

    // Create individual customer.
    $user = $this->drupalCreateUser([
      'delete any crm_core_individual entity of bundle customer',
      'create crm_core_individual entities',
      'edit any crm_core_individual entity',
      'administer individual types',
      'view any crm_core_individual entity',
    ]);
    $this->drupalLogin($user);
    $customer_node = [
      'name[0][title]' => 'Mr.',
      'name[0][given]' => 'John',
      'name[0][middle]' => 'Emanuel',
      'name[0][family]' => 'Smith',
      'name[0][generational]' => 'IV',
      'name[0][credentials]' => '',
    ];
    $this->drupalPostForm('crm-core/individual/add/customer', $customer_node, 'Save Customer');

    // Assert we were redirected back to the list of individuals.
    $this->assertUrl('crm-core/individual');

    $this->assertSession()->linkExists('John Smith', 0, 'Newly created individual title listed.');
    $this->assertSession()->pageTextContains('Customer');

    // Assert all view headers are available.
    $this->assertSession()->linkExists('Name');
    $this->assertSession()->linkExists('Individual Type');
    $this->assertSession()->linkExists('Updated');
    $this->assertSession()->pageTextContains('Operations links');

    $elements = $this->xpath('//form[@class="views-exposed-form"]/div/label[text()="Name (given)"]');
    $this->assertCount(1, $elements);

    $elements = $this->xpath('//form[@class="views-exposed-form"]/div/label[text()="Name (family)"]');
    $this->assertCount(1, $elements);

    $elements = $this->xpath('//form[@class="views-exposed-form"]/div/label[text()="Type"]');
    $this->assertCount(1, $elements);

    $individuals = \Drupal::entityTypeManager()->getStorage('crm_core_individual')->loadByProperties(['name__given' => 'John', 'name__family' => 'Smith']);
    $individual = current($individuals);

    $this->assertSession()->linkByHrefExists('crm-core/individual/' . $individual->id());

    // Edit link is available.
    $this->assertRaw('crm-core/individual/' . $individual->id() . '/edit');
    // Delete link is available'.
    $this->assertRaw('crm-core/individual/' . $individual->id() . '/delete');

    // Individual updated date is available.
    $this->assertSession()->pageTextContains($this->container->get('date.formatter')->format($individual->get('changed')->value, 'medium'));

    $this->drupalGet('crm-core/individual/1/edit');
    // Delete link is available.
    $this->assertRaw('crm-core/individual/1/delete" class="button button--danger" data-drupal-selector="edit-delete" id="edit-delete"');

    $individual->save();

    // Get test view data page.
    $this->drupalGet('individual-view-data');
    $this->assertSession()->pageTextContains('Mr. John Emanuel Smith IV');

    // Edit customer individual.
    $customer_node = [
      'name[0][title]' => 'Mr.',
      'name[0][given]' => 'Maynard',
      'name[0][middle]' => 'James',
      'name[0][family]' => 'Keenan',
      'name[0][generational]' => 'I',
      'name[0][credentials]' => 'MJK',
    ];
    $individuals = $this->container->get('entity_type.manager')
      ->getStorage('crm_core_individual')
      ->loadByProperties(['name__given' => 'John', 'name__family' => 'Smith']);
    $individual = current($individuals);
    $this->drupalPostForm('crm-core/individual/' . $individual->id() . '/edit', $customer_node, 'Save Customer');
    // Assert we are viewing the updated entity after update.
    $this->assertUrl('crm-core/individual/' . $individual->id());
    // Local task "Delete" is available.
    $this->assertRaw('data-drupal-link-system-path="crm-core/individual/' . $individual->id() . '/delete"');

    // Check listing page.
    $this->drupalGet('crm-core/individual');
    // Updated customer individual title listed.
    $this->assertSession()->pageTextContains('Maynard Keenan');

    // Delete individual contact.
    $this->drupalPostForm('crm-core/individual/' . $individual->id() . '/delete', [], 'Delete');
    $this->assertUrl('crm-core/individual');
    // Deleted individual customer title no more listed.
    $this->assertSession()->linkNotExists('Maynard Keenan');

    // Assert that there are no contacts left.
    // No individuals available after deleting all of them.
    $this->assertSession()->pageTextContains('There are no individuals available.');

    // Create a individual with no label.
    /** @var \Drupal\crm_core_contact\ContactInterface $individual */
    $individual = Individual::create(['type' => 'customer']);
    $individual->save();

    // Create another user.
    $new_user = $this->drupalCreateUser();

    // Test EntityOwnerTrait functions on contact.
    $this->assertEqual($individual->getOwnerId(), $user->id());
    $this->assertEqual($individual->getOwner()->id(), $user->id());
    $individual->setOwner($new_user);
    $this->assertEqual($individual->getOwnerId(), $new_user->id());
    $this->assertEqual($individual->getOwner()->id(), $new_user->id());
    $individual->setOwnerId($user->id());
    $this->assertEqual($individual->getOwnerId(), $user->id());
    $this->assertEqual($individual->getOwner()->id(), $user->id());

    // Test whether owner is loaded from database correctly.
    // No need to save individual here, since nothing has changed.
    $id = $individual->id();
    $individual = Individual::load($id);
    $this->assertEqual($individual->getOwnerId(), $user->id());
    $this->assertEqual($individual->getOwner()->id(), $user->id());

    // And then make sure the result is correct even with owner being someone
    // else than the user who was logged in while creating entity.
    $individual->setOwnerId($new_user->id());
    $individual->save();
    $id = $individual->id();
    $individual = Individual::load($id);
    $this->assertEqual($individual->getOwnerId(), $new_user->id());
    $this->assertEqual($individual->getOwner()->id(), $new_user->id());

    // Go to overview page and assert there is a default label displayed.
    $this->drupalGet('crm-core/individual');
    $this->assertSession()->linkExists('Nameless #' . $individual->id());
    $this->assertSession()->linkByHrefExists('crm-core/individual/' . $individual->id());
  }

  /**
   * Tests the individual type operations.
   *
   * User with permissions 'administer individual types' should be able to
   * create/edit/delete individual types.
   *
   * @covers \Drupal\crm_core_contact\Form\IndividualTypeForm::buildForm
   * @covers \Drupal\crm_core_contact\Form\IndividualTypeForm::submitForm
   */
  public function testIndividualTypeOperations(): void {
    // Given I am logged in as a user with permission 'administer individual
    // types'.
    $user = $this->drupalCreateUser(['administer individual types']);
    $this->drupalLogin($user);

    // When I visit the individual type admin page.
    $this->drupalGet('admin/structure/crm-core/individual-types');

    // Then I should see edit, and delete links for existing contacts.
    $this->assertSession()->linkByHrefExists('admin/structure/crm-core/individual-types/customer', 0);
    $this->assertSession()->linkByHrefExists('admin/structure/crm-core/individual-types/customer/delete', 0);

    // Given there is a individual of type 'customer.'.
    Individual::create(['type' => 'customer'])->save();

    // When I visit the individual type admin page.
    $this->drupalGet('admin/structure/crm-core/individual-types');

    // Then I should not see a delete link.
    $this->assertSession()->linkByHrefNotExists('admin/structure/crm-core/individual-types/customer/delete');
    $this->drupalGet('admin/structure/crm-core/individual-types/customer/delete');
    $this->assertSession()->statusCodeEquals(403);

    // When I edit the individual type.
    $this->drupalGet('admin/structure/crm-core/individual-types/customer');
    $this->assertSession()->statusCodeEquals(200);

    // Then I should see "Save individual type" button.
    $this->assertSession()->buttonExists('Save individual type');
    // Then I should not see a delete link.
    $this->assertSession()->linkByHrefNotExists('admin/structure/crm-core/individual-types/customer/delete');
  }

  /**
   * Test if the field UI is displayed on individual bundle.
   */
  public function testFieldsUi(): void {
    $user = $this->drupalCreateUser([
      'administer crm_core_individual display',
      'administer crm_core_individual form display',
      'administer crm_core_individual fields',
      'administer individual types',
    ]);
    $this->drupalLogin($user);

    // List of all types.
    $this->drupalGet('admin/structure/crm-core/individual-types');
    // Manage fields local task in available.
    $this->assertSession()->linkExists('Edit');
    $this->assertSession()->linkExists('Manage fields');
    $this->assertSession()->linkExists('Manage form display');
    $this->assertSession()->linkExists('Manage display');

    // Edit on type.
    $this->drupalGet('admin/structure/crm-core/individual-types/customer');
    // Manage fields local task in available.
    $this->assertSession()->linkExists('Edit');
    $this->assertSession()->linkExists('Manage fields');
    $this->assertSession()->linkExists('Manage form display');
    $this->assertSession()->linkExists('Manage display');

    // Manage fields on type.
    $this->drupalGet('admin/structure/crm-core/individual-types/customer/fields');
    // Manage fields local task in available.
    $this->assertSession()->linkExists('Edit');
    $this->assertSession()->linkExists('Manage fields');
    $this->assertSession()->linkExists('Manage form display');
    $this->assertSession()->linkExists('Manage display');

    $this->drupalGet('admin/structure/crm-core/individual-types/customer/form-display');
    // Name field is available on form display.
    $this->assertSession()->pageTextContains('Name');

    $this->drupalGet('admin/structure/crm-core/individual-types/customer/display');
    // Name field is available on manage display.
    $this->assertSession()->pageTextContains('Name');
  }

  /**
   * Test individual revisions.
   */
  public function testIndividualRevisions() {

    $user = $this->drupalCreateUser([
      'create crm_core_individual entities',
      'view any crm_core_individual entity',
      'edit any crm_core_individual entity',
      'view all crm_core_individual revisions',
      'revert all crm_core_individual revisions',
    ]);
    $this->drupalLogin($user);

    $this->drupalPostForm('crm-core/individual/add/customer', ['name[0][given]' => 'rev', 'name[0][family]' => '1'], 'Save Customer');
    $this->drupalPostForm('crm-core/individual/1/edit', ['name[0][family]' => '2'], 'Save Customer');
    $this->drupalPostForm('crm-core/individual/1/edit', ['name[0][family]' => '3'], 'Save Customer');

    $this->clickLink('Revisions');
    $this->assertSession()->linkByHrefExists('crm-core/individual/1');
    $this->assertSession()->linkByHrefExists('crm-core/individual/1/revisions/1/view');
    $this->assertSession()->linkByHrefExists('crm-core/individual/1/revisions/2/view');

    $this->drupalGet('crm-core/individual/1/revisions/1/view');
    $this->assertSession()->pageTextContains('rev 1');
    $this->drupalGet('crm-core/individual/1/revisions/2/view');
    $this->assertSession()->pageTextContains('rev 2');

    /** @var \Drupal\crm_core_contact\ContactInterface $individual */
    $individual = Individual::create([
      'type' => 'customer',
      'name' => [
        [
          'given' => 'Second',
          'family' => 'Individual',
        ],
      ],
    ]);
    $individual->save();

    $individual->setNewRevision(TRUE);
    $individual->isDefaultRevision(FALSE);
    $individual->save();

    $this->drupalGet($individual->toUrl('version-history'));
    // Assert we have one revision link and current revision.
    $this->assertEqual($individual->getRevisionId(), 5);
    $this->assertSession()->linkByHrefExists('crm-core/individual/' . $individual->id() . '/revisions/5/view');
    $this->assertSession()->linkByHrefExists('crm-core/individual/' . $individual->id());

    // Assert we have revision revert link.
    $this->assertSession()->linkByHrefExists('crm-core/individual/' . $individual->id() . '/revisions/5/revert');
    $this->drupalGet('crm-core/individual/' . $individual->id() . '/revisions/5/revert');
    $this->assertSession()->statusCodeEquals(200);

    // Check view revision route.
    $this->drupalGet('crm-core/individual/' . $individual->id() . '/revisions/5/view');
    $this->assertSession()->pageTextContains('Second Individual');
  }

  /**
   * Test list builder views for contact entities.
   *
   * @covers \Drupal\crm_core_contact\IndividualListBuilder::render
   */
  public function testListBuilder(): void {
    $user = $this->drupalCreateUser([
      'view any crm_core_individual entity',
      'view any crm_core_organization entity',
      'administer views',
    ]);
    $this->drupalLogin($user);

    // Delete created organization view to get default view from list builder.
    $this->drupalGet('admin/structure/views/view/crm_core_organization_overview/delete');
    $this->drupalPostForm(NULL, [], 'Delete');
    // Check organization collection page.
    $this->drupalGet('/crm-core/organization');
    $this->assertSession()->statusCodeEquals(200);
    // Delete created individual view to get default view from list builder.
    $this->drupalGet('admin/structure/views/view/crm_core_individual_overview/delete');
    $this->drupalPostForm(NULL, [], 'Delete');
    // Assert response on individual collection page.
    $this->drupalGet('/crm-core/individual');
    $this->assertSession()->statusCodeEquals(200);
  }

}
