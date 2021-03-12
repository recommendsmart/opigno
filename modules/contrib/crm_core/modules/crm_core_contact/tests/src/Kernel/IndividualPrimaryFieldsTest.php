<?php

namespace Drupal\Tests\crm_core_contact\Kernel;

use Drupal\crm_core_contact\Entity\Individual;
use Drupal\crm_core_contact\Entity\IndividualType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests primary fields in Individual.
 *
 * @group crm_core
 * @requires module address
 * @coversDefaultClass \Drupal\crm_core_contact\Entity\Individual
 */
class IndividualPrimaryFieldsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'text',
    'user',
    'crm_core',
    'crm_core_contact',
    'name',
    'options',
    'address',
    'telephone',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['field']);
    $this->installEntitySchema('crm_core_individual');
  }

  /**
   * Test primary fields.
   *
   * @covers ::getPrimaryAddress
   * @covers ::getPrimaryPhone
   * @covers ::getPrimaryEmail
   * @covers ::getPrimaryField
   */
  public function testPrimaryFields() {
    $type = IndividualType::create([
      'name' => 'Customer',
      'type' => 'customer',
      'primary_fields' => [
        'phone' => 'field_phone',
        'address' => 'field_address',
        'email' => 'field_email',
      ],
    ]);
    $type->save();
    FieldStorageConfig::create([
      'entity_type' => 'crm_core_individual',
      'type' => 'address',
      'field_name' => 'field_address',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'crm_core_individual',
      'bundle' => 'customer',
      'field_name' => 'field_address',
    ])->save();
    FieldStorageConfig::create([
      'entity_type' => 'crm_core_individual',
      'type' => 'email',
      'field_name' => 'field_email',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'crm_core_individual',
      'bundle' => 'customer',
      'field_name' => 'field_email',
    ])->save();
    FieldStorageConfig::create([
      'entity_type' => 'crm_core_individual',
      'type' => 'telephone',
      'field_name' => 'field_phone',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'crm_core_individual',
      'bundle' => 'customer',
      'field_name' => 'field_phone',
    ])->save();
    /** @var \Drupal\crm_core_contact\Entity\Individual $individual */
    $individual = Individual::create([
      'name' => [
        'family' => 'Jane',
        'given' => 'Doe',
      ],
      'type' => 'customer',
      'field_phone' => '+15551234',
      'field_address' => [
        'country_code' => 'US',
      ],
      'field_email' => 'jane@example.com',
    ]);
    $individual->save();
    $this->assertEqual($individual->getPrimaryEmail()->value, 'jane@example.com');
    $this->assertEqual($individual->getPrimaryPhone()->value, '+15551234');
    $this->assertEqual($individual->getPrimaryAddress()->getValue()[0]['country_code'], 'US');
  }

}
