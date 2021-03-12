<?php

namespace Drupal\Tests\crm_core_contact\Kernel;

use Drupal\crm_core_contact\Entity\Individual;
use Drupal\crm_core_contact\Entity\IndividualType;
use Drupal\crm_core_contact\Menu\ContactLocalTaskProvider;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the local task provider.
 *
 * @group crm_core
 */
class LocalTaskTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'crm_core_contact',
    'user',
    'name',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('crm_core_individual');
    $this->installEntitySchema('user');
  }

  /**
   * Test local tasks.
   *
   * @covers \Drupal\crm_core_contact\Menu\ContactLocalTaskProvider::buildLocalTasks
   */
  public function testLocalTask(): void {
    $type = IndividualType::create([
      'name' => 'Consumer',
      'type' => 'consumer',
      'primary_fields' => [],
    ]);
    $type->save();
    $individual = Individual::create(['type' => 'consumer']);
    $individual->save();
    $provider = new ContactLocalTaskProvider(
      $individual->getEntityType(),
      $this->container->get('string_translation')
    );
    $tasks = $provider->buildLocalTasks($individual->getEntityType());
    $this->assertEqual([
      'entity.crm_core_individual.canonical' => [
        'title' => 'View',
        'route_name' => 'entity.crm_core_individual.canonical',
        'base_route' => 'entity.crm_core_individual.canonical',
        'weight' => 0,
      ],
      'entity.crm_core_individual.edit_form' => [
        'title' => 'Edit',
        'route_name' => 'entity.crm_core_individual.edit_form',
        'base_route' => 'entity.crm_core_individual.canonical',
        'weight' => 10,
      ],
      'entity.crm_core_individual.delete_form' => [
        'title' => 'Delete',
        'route_name' => 'entity.crm_core_individual.delete_form',
        'base_route' => 'entity.crm_core_individual.canonical',
        'weight' => 20,
      ],
      'entity.crm_core_individual.version_history' => [
        'title' => 'Revisions',
        'route_name' => 'entity.crm_core_individual.version_history',
        'base_route' => 'entity.crm_core_individual.canonical',
        'weight' => 30,
      ],
    ], $tasks);
  }

}
