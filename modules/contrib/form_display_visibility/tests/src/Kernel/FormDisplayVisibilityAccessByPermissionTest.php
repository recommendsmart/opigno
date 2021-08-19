<?php

namespace Drupal\Tests\form_display_visibility\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;

/**
 * Tests the entity display configuration entities.
 *
 * @group form_display_visibility
 */
class FormDisplayVisibilityAccessByPermissionTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   *
   * @TODO Publish the schema so this is not needed.
   */
  protected static $configSchemaCheckerExclusions = [
    'core.entity_form_display.entity_test.entity_test.default',
  ];

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName = 'test_field';

  /**
   * The test field storage config.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field config.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * A custom role for testing purposes.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $role;

  /**
   * Default entity form display settings.
   *
   * @var array
   */
  protected $entityFormDisplaySettings = [
    'targetEntityType' => 'entity_test',
    'bundle' => 'entity_test',
    'mode' => 'default',
    'status' => TRUE,
    'third_party_settings' => [],
  ];

  protected $defaultWidget;
  protected $widgetSettings;

  /**
   * Modules to install.
   *
   * @var string[]
   */
  public static $modules = [
    'system',
    'field_ui',
    'field',
    'entity_test',
    'field_test',
    'user',
    'text',
    'form_display_visibility',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['form_display_visibility']);

    // Create a field storage and a field.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
    ]);
    $this->field->save();

    $field_type_info = \Drupal::service('plugin.manager.field.field_type')->getDefinition($this->fieldStorage->getType());
    $this->defaultWidget = $field_type_info['default_widget'];
    $this->widgetSettings = \Drupal::service('plugin.manager.field.widget')->getDefaultSettings($this->defaultWidget);

    // Create an admin user so the ones created in the test don't have uid 1.
    $this->admin = $this->createUser();

    // Create new role.
    $rid = strtolower($this->randomMachineName(8));
    $label = $this->randomString(8);
    $role = Role::create([
      'id' => $rid,
      'label' => $label,
    ]);
    $role->save();
    $this->role = $role;
  }

  /**
   * Tests the regular case with no third party settings.
   */
  public function testNoSettings() {
    $form_display = EntityFormDisplay::create($this->entityFormDisplaySettings);
    $form_display->setComponent($this->fieldName);
    $form_display->save();
    $expected = [
      'weight' => 3,
      'type' => $this->defaultWidget,
      'settings' => $this->widgetSettings,
      'third_party_settings' => [],
      'region' => 'content',
    ];
    $this->assertEqual($form_display->getComponent($this->fieldName), $expected);

    $normal_user = $this->createUser();
    $this->setCurrentUser($normal_user);

    $entity = EntityTest::create([]);
    $form = $this->container->get('entity.form_builder')->getForm($entity, 'default');
    $this->assertArrayHasKey($this->fieldName, $form);
    $this->assertEquals($form['test_field']['widget']['#access'], TRUE);
  }

  /**
   * Test that the field is not visible with no permissions.
   */
  public function testNoPermissions() {
    $form_display = EntityFormDisplay::create($this->entityFormDisplaySettings);
    $third_party_settings = [
      'form_display_visibility' => [
        'conditions' => [
          'access_by_permission' => [
            'enabled' => TRUE,
            'perm' => 'view test entity',
          ],
        ],
      ],
    ];
    $form_display->setComponent($this->fieldName, ['third_party_settings' => $third_party_settings]);
    $form_display->save();
    $expected = [
      'weight' => 3,
      'type' => $this->defaultWidget,
      'settings' => $this->widgetSettings,
      'region' => 'content',
      'third_party_settings' => $third_party_settings,
    ];
    $this->assertEqual($form_display->getComponent($this->fieldName), $expected);

    $normal_user = $this->createUser();
    $this->setCurrentUser($normal_user);

    $entity = EntityTest::create([]);
    $form = $this->container->get('entity.form_builder')->getForm($entity, 'default');
    $this->assertArrayHasKey('test_field', $form);
    $this->assertEquals($form['test_field']['widget']['#access'], FALSE);
  }

  /**
   * Test that the field is not visible with disabled permissions.
   */
  public function testDisabledPermissions() {
    $form_display = EntityFormDisplay::create($this->entityFormDisplaySettings);
    $third_party_settings = [
      'form_display_visibility' => [
        'conditions' => [
          'access_by_permission' => [
            'enabled' => FALSE,
            'perm' => 'view test entity',
          ],
        ],
      ],
    ];
    $form_display->setComponent($this->fieldName, ['third_party_settings' => $third_party_settings]);
    $form_display->save();
    $expected = [
      'weight' => 3,
      'type' => $this->defaultWidget,
      'settings' => $this->widgetSettings,
      'region' => 'content',
      'third_party_settings' => $third_party_settings,
    ];
    $this->assertEqual($form_display->getComponent($this->fieldName), $expected);

    $normal_user = $this->createUser();
    $this->setCurrentUser($normal_user);

    $entity = EntityTest::create([]);
    $form = $this->container->get('entity.form_builder')->getForm($entity, 'default');
    $this->assertArrayHasKey('test_field', $form);
    $this->assertEquals($form['test_field']['widget']['#access'], TRUE);
  }

  /**
   * Tests that the user has access to the field with the right permissions.
   */
  public function testWithPermissions() {
    $form_display = EntityFormDisplay::create($this->entityFormDisplaySettings);
    $third_party_settings = [
      'form_display_visibility' => [
        'conditions' => [
          'access_by_permission' => [
            'enabled' => TRUE,
            'perm' => 'view test entity',
          ],
        ],
      ],
    ];
    $form_display->setComponent($this->fieldName, ['third_party_settings' => $third_party_settings]);
    $form_display->save();
    $expected = [
      'weight' => 3,
      'type' => $this->defaultWidget,
      'settings' => $this->widgetSettings,
      'region' => 'content',
      'third_party_settings' => $third_party_settings,
    ];
    $this->assertEqual($form_display->getComponent($this->fieldName), $expected);

    $normal_user = $this->createUser(['view test entity']);
    $this->setCurrentUser($normal_user);

    $entity = EntityTest::create([]);
    $form = $this->container->get('entity.form_builder')->getForm($entity, 'default');
    $this->assertArrayHasKey('test_field', $form);
    $this->assertEquals($form['test_field']['widget']['#access'], TRUE);
  }

  /**
   * Test that the field is not visible with disabled roles.
   */
  public function testDisabledRole() {
    $form_display = EntityFormDisplay::create($this->entityFormDisplaySettings);
    $third_party_settings = [
      'form_display_visibility' => [
        'conditions' => [
          'access_by_role' => [
            'enabled' => FALSE,
            'role' => [$this->role->id()],
          ],
        ],
      ],
    ];
    $form_display->setComponent($this->fieldName, ['third_party_settings' => $third_party_settings]);
    $form_display->save();
    $expected = [
      'weight' => 3,
      'type' => $this->defaultWidget,
      'settings' => $this->widgetSettings,
      'region' => 'content',
      'third_party_settings' => $third_party_settings,
    ];
    $this->assertEqual($form_display->getComponent($this->fieldName), $expected);

    $normal_user = $this->createUser();
    $this->setCurrentUser($normal_user);

    $entity = EntityTest::create([]);
    $form = $this->container->get('entity.form_builder')->getForm($entity, 'default');
    $this->assertArrayHasKey('test_field', $form);
    $this->assertEquals($form['test_field']['widget']['#access'], TRUE);
  }

  /**
   * Test that the field is not visible with no roles.
   */
  public function testNoRole() {
    $form_display = EntityFormDisplay::create($this->entityFormDisplaySettings);
    $third_party_settings = [
      'form_display_visibility' => [
        'conditions' => [
          'access_by_role' => [
            'enabled' => TRUE,
            'role' => [$this->role->id()],
          ],
        ],
      ],
    ];
    $form_display->setComponent($this->fieldName, ['third_party_settings' => $third_party_settings]);
    $form_display->save();
    $expected = [
      'weight' => 3,
      'type' => $this->defaultWidget,
      'settings' => $this->widgetSettings,
      'region' => 'content',
      'third_party_settings' => $third_party_settings,
    ];
    $this->assertEqual($form_display->getComponent($this->fieldName), $expected);

    $normal_user = $this->createUser();
    $this->setCurrentUser($normal_user);

    $entity = EntityTest::create([]);
    $form = $this->container->get('entity.form_builder')->getForm($entity, 'default');
    $this->assertArrayHasKey('test_field', $form);
    $this->assertEquals($form['test_field']['widget']['#access'], FALSE);
  }

  /**
   * Tests that the user has access to the field with the right role.
   */
  public function testWithRole() {
    $form_display = EntityFormDisplay::create($this->entityFormDisplaySettings);
    $third_party_settings = [
      'form_display_visibility' => [
        'conditions' => [
          'access_by_role' => [
            'enabled' => TRUE,
            'role' => [$this->role->id()],
          ],
        ],
      ],
    ];
    $form_display->setComponent($this->fieldName, ['third_party_settings' => $third_party_settings]);
    $form_display->save();
    $expected = [
      'weight' => 3,
      'type' => $this->defaultWidget,
      'settings' => $this->widgetSettings,
      'region' => 'content',
      'third_party_settings' => $third_party_settings,
    ];
    $this->assertEqual($form_display->getComponent($this->fieldName), $expected);

    $normal_user = $this->createUser();
    $normal_user->addRole($this->role->id());
    $this->setCurrentUser($normal_user);

    $entity = EntityTest::create([]);
    $form = $this->container->get('entity.form_builder')->getForm($entity, 'default');
    $this->assertArrayHasKey('test_field', $form);
    $this->assertEquals($form['test_field']['widget']['#access'], TRUE);
  }

  /**
   * Tests role and permission at the same time.
   */
  public function testRolePlusPermission() {
    $form_display = EntityFormDisplay::create($this->entityFormDisplaySettings);
    $third_party_settings = [
      'form_display_visibility' => [
        'conditions' => [
          'access_by_role' => [
            'enabled' => TRUE,
            'role' => [$this->role->id()],
          ],
          'access_by_permission' => [
            'enabled' => TRUE,
            'perm' => 'view test entity',
          ],
        ],
      ],
    ];
    $form_display->setComponent($this->fieldName, ['third_party_settings' => $third_party_settings]);
    $form_display->save();
    $expected = [
      'weight' => 3,
      'type' => $this->defaultWidget,
      'settings' => $this->widgetSettings,
      'region' => 'content',
      'third_party_settings' => $third_party_settings,
    ];
    $this->assertEqual($form_display->getComponent($this->fieldName), $expected);

    $normal_user = $this->createUser(['view test entity']);
    $normal_user->addRole($this->role->id());
    $this->setCurrentUser($normal_user);

    $entity = EntityTest::create([]);
    $form = $this->container->get('entity.form_builder')->getForm($entity, 'default');
    $this->assertArrayHasKey('test_field', $form);
    $this->assertEquals($form['test_field']['widget']['#access'], TRUE);
  }

  /**
   * Tests no role but permission.
   */
  public function testNoRoleButPermission() {
    $form_display = EntityFormDisplay::create($this->entityFormDisplaySettings);
    $third_party_settings = [
      'form_display_visibility' => [
        'conditions' => [
          'access_by_role' => [
            'enabled' => TRUE,
            'role' => [$this->role->id()],
          ],
          'access_by_permission' => [
            'enabled' => TRUE,
            'perm' => 'view test entity',
          ],
        ],
      ],
    ];
    $form_display->setComponent($this->fieldName, ['third_party_settings' => $third_party_settings]);
    $form_display->save();
    $expected = [
      'weight' => 3,
      'type' => $this->defaultWidget,
      'settings' => $this->widgetSettings,
      'region' => 'content',
      'third_party_settings' => $third_party_settings,
    ];
    $this->assertEqual($form_display->getComponent($this->fieldName), $expected);

    $normal_user = $this->createUser(['view test entity']);
    $this->setCurrentUser($normal_user);

    $entity = EntityTest::create([]);
    $form = $this->container->get('entity.form_builder')->getForm($entity, 'default');
    $this->assertArrayHasKey('test_field', $form);
    $this->assertEquals($form['test_field']['widget']['#access'], FALSE);
  }

  /**
   * Tests role but not permission.
   */
  public function testRoleButNotPermission() {
    $form_display = EntityFormDisplay::create($this->entityFormDisplaySettings);
    $third_party_settings = [
      'form_display_visibility' => [
        'conditions' => [
          'access_by_role' => [
            'enabled' => TRUE,
            'role' => [$this->role->id()],
          ],
          'access_by_permission' => [
            'enabled' => TRUE,
            'perm' => 'view test entity',
          ],
        ],
      ],
    ];
    $form_display->setComponent($this->fieldName, ['third_party_settings' => $third_party_settings]);
    $form_display->save();
    $expected = [
      'weight' => 3,
      'type' => $this->defaultWidget,
      'settings' => $this->widgetSettings,
      'region' => 'content',
      'third_party_settings' => $third_party_settings,
    ];
    $this->assertEqual($form_display->getComponent($this->fieldName), $expected);

    $normal_user = $this->createUser();
    $normal_user->addRole($this->role->id());
    $this->setCurrentUser($normal_user);

    $entity = EntityTest::create([]);
    $form = $this->container->get('entity.form_builder')->getForm($entity, 'default');
    $this->assertArrayHasKey('test_field', $form);
    $this->assertEquals($form['test_field']['widget']['#access'], FALSE);
  }

}
