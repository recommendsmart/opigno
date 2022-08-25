<?php

declare(strict_types=1);

namespace Drupal\Tests\grouped_checkboxes\Functional;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\FieldStorageDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests the grouped checkboxes widget.
 *
 * @group grouped_checkboxes
 */
class GroupedCheckboxesTest extends BrowserTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'system',
    'grouped_checkboxes',
    'entity_test',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Array of terms.
   *
   * @var array
   */
  protected $terms = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create admin user.
    $this->adminUser = $this->drupalCreateUser([
      'administer entity_test content',
      'access content',
    ]);

    $field_name = mb_strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'entity_reference',
      'cardinality' => FieldStorageDefinition::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ])->save();
    $this->fieldName = $field_name;
    \Drupal::service('entity_display.repository')->getFormDisplay('entity_test', 'entity_test')->setComponent($field_name, [
      'type' => 'grouped_checkboxes',
    ])->save();
    $vocab1 = $this->createVocabulary();
    $vocab2 = $this->createVocabulary();
    foreach ([$vocab1, $vocab2] as $vocab) {
      foreach (range(0, 2) as $delta) {
        $this->terms[] = $this->createTerm($vocab);
      }
    }
  }

  /**
   * Tests grouped checkboxes UI.
   */
  public function testGroupedCheckboxesUi(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/entity_test/add');
    $name = $this->randomMachineName();
    $term_ids = array_map(function (TermInterface $term) {
      return $term->id();
    }, $this->terms);
    $values = [
      'name[0][value]' => $name,
    ];
    foreach ($term_ids as $term_id) {
      $values[sprintf('%s[%d]', $this->fieldName, $term_id)] = TRUE;
    }
    $this->submitForm($values, 'Save');
    $entities = \Drupal::entityTypeManager()->getStorage('entity_test')->loadByProperties(['name' => $name]);
    $this->assertCount(1, $entities);
    $entity = reset($entities);
    assert($entity instanceof EntityTest);
    $saved = array_column($entity->get($this->fieldName)->getValue(), 'target_id');
    sort($saved);
    $this->assertEquals(array_values($term_ids), $saved);
  }

}
