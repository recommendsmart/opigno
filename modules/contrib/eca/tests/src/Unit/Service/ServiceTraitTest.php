<?php

namespace Drupal\Tests\eca\Unit\Service;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca\Plugin\ECA\Condition\StringComparisonBase;
use Drupal\eca\Service\Conditions;
use Drupal\eca\Service\ServiceTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests the service trait.
 *
 * @group eca
 */
class ServiceTraitTest extends UnitTestCase {

  use ServiceTrait;

  /**
   * Gets the expected option fields.
   *
   * @param string $name
   * @param string $label
   *
   * @return array
   */
  private static function getExpectedOptionFields(string $name, string $label): array {
    return [
      'name' => $name,
      'label' => $label,
      'weight' => 0,
      'type' => 'Dropdown',
      'value' => 'testValue',
      'extras' => [
        'choices' => [
          [
            'name' => 'name1',
            'value' => 'value1',
          ],
          [
            'name' => 'name2',
            'value' => 'value2',
          ],
          [
            'name' => 'name3',
            'value' => 'value3',
          ],
        ],
      ],
    ];
  }

  /**
   * Gets the expected checkbox.
   *
   * @param string $name
   * @param string $label
   * @param string $condition
   *
   * @return array
   */
  private static function getExpectedCheckbox(string $name, string $label, string $condition): array {
    return [
      'name' => $name,
      'label' => $label,
      'weight' => 0,
      'type' => 'Dropdown',
      'value' => $condition,
      'extras' => [
        'choices' => [
          [
            'name' => 'no',
            'value' => Conditions::OPTION_NO,
          ],
          [
            'name' => 'yes',
            'value' => Conditions::OPTION_YES,
          ],
        ],
      ],
    ];
  }

  /**
   * Tests the sort of plugins.
   *
   * @return void
   */
  public function testSortPlugins(): void {
    $plugins = [];
    foreach ([
               'testPluginB',
               'testPluginC',
               'testPluginA',
               'testPluginC',
             ] as $label) {
      $plugins[] = $this->getPluginMock($label);
    }
    $this->sortPlugins($plugins);
    foreach ([
               'testPluginA',
               'testPluginB',
               'testPluginC',
               'testPluginC',
             ] as $key => $label) {
      $this->assertEquals($label, $plugins[$key]->getPluginDefinition()['label']);
    }
  }

  /**
   * Gets plugin mocks by the given label.
   *
   * @param string $label
   *
   * @return MockObject
   */
  private function getPluginMock(string $label): MockObject {
    $mockObject = $this->createMock(PluginInspectionInterface::class);
    $mockObject->method('getPluginDefinition')->willReturn([
      'label' => $label,
    ]);
    return $mockObject;
  }

  /**
   * Tests the method prepare config fields with boolean.
   *
   * @return void
   */
  public function testPrepareConfigFieldsWithBoolean(): void {
    $fields = [];
    $pluginInspection = $this->createMock(PluginInspectionInterface::class);
    $config = [
      'testKey' => TRUE,
    ];
    $this->prepareConfigFields($fields, $config, $pluginInspection);
    $this->assertEquals(self::getExpectedCheckbox('testKey', 'TestKey', Conditions::OPTION_YES),
      $fields[0]);
  }

  /**
   * Tests the method prepare config fields with array.
   *
   * @return void
   */
  public function testPrepareConfigFieldsWithArray(): void {
    $fields = [];
    $pluginInspection = $this->createMock(PluginInspectionInterface::class);
    $config = [
      'testKey' => [
        'a',
        'b',
        'c',
      ],
    ];
    $this->prepareConfigFields($fields, $config, $pluginInspection);
    $expectedFields = [
      'name' => 'testKey',
      'label' => 'TestKey',
      'weight' => 0,
      'type' => 'String',
      'value' => 'a,b,c',
    ];
    $this->assertEquals($expectedFields, $fields[0]);
  }

  /**
   * Tests the method prepare config fields with options.
   *
   * @return void
   */
  public function testPrepareConfigFieldsWithOptions(): void {
    $fields = [];
    $pluginInspection = $this->createMock(StringComparisonBase::class);
    $pluginInspection->expects($this->once())->method('getOptions')
      ->with('testKey')->willReturn([
        'value1' => 'name1',
        'value2' => 'name2',
        'value3' => 'name3',
      ]);
    $config = [
      'testKey' => 'testValue',
    ];
    $this->prepareConfigFields($fields, $config, $pluginInspection);
    $this->assertEquals(self::getExpectedOptionFields('testKey', 'TestKey'),
      $fields[0]);
  }

  /**
   * Tests the method prepare config fields with form textarea.
   *
   * @return void
   */
  public function testPrepareConfigFieldsWithFormTextarea(): void {
    $fields = [];
    $pluginInspection = $this->getActionBaseMockByType('textarea');
    $config = [
      'testKey' => 'testValue',
    ];
    $this->prepareConfigFields($fields, $config, $pluginInspection);
    $expectedFields = [];
    $expectedFields[] = [
      'name' => 'testKey',
      'label' => 'title',
      'weight' => 0,
      'type' => 'Text',
      'value' => 'testValue',
    ];
    $this->assertEquals($expectedFields, $fields);
  }

  /**
   * Tests the method prepare config fields with form select.
   *
   * @return void
   */
  public function testPrepareConfigFieldsWithFormSelect(): void {
    $fields = [];
    $pluginInspection = $this->getActionBaseMockByType('select');
    $config = [
      'testKey' => 'testValue',
    ];
    $this->prepareConfigFields($fields, $config, $pluginInspection);
    $expectedFields = [];
    $expectedFields[] = [
      'name' => 'testKey',
      'label' => 'title',
      'weight' => 0,
      'type' => 'Dropdown',
      'value' => 'testValue',
      'extras' => [
        'choices' => [
          [
            'name' => 'name1',
            'value' => 'value1',
          ],
          [
            'name' => 'name2',
            'value' => 'value2',
          ],
        ],
      ],
    ];
    $this->assertEquals($expectedFields, $fields);
  }

  /**
   * Gets the mock by the given type.
   *
   * @param string $type
   *
   * @return PluginInspectionInterface
   */
  private function getActionBaseMockByType(string $type): PluginInspectionInterface {
    $pluginInspection = $this->createMock(ConfigurableActionBase::class);
    $form = [];
    $form['testKey'] = [
      '#title' => 'title',
      '#type' => $type,
    ];
    if ($type === 'select') {
      $form['testKey']['#options'] = [
        'value1' => 'name1',
        'value2' => 'name2',
      ];
    }
    $pluginInspection->expects($this->once())->method('buildConfigurationForm')
      ->willReturn($form);
    return $pluginInspection;
  }

  /**
   * Tests the method fieldLabel with NULL value.
   *
   * @return void
   */
  public function testFieldLabelWithNull(): void {
    $this->assertEquals('A test key',
      $this->fieldLabel(NULL, 'a_test_key'));
  }

  /**
   * Tests the method fieldLabel.
   *
   * @return void
   */
  public function testFieldLabelWithLabel(): void {
    $this->assertEquals('Test Label',
      $this->fieldLabel('Test Label', 'a_test_key'));
  }

  /**
   * Tests the optionsField method.
   *
   * @return void
   */
  public function testOptionsField(): void {
    $expected = self::getExpectedOptionFields('test', 'This is a test');

    $this->assertEquals($expected, $this->optionsField('test', 'This is a test', 0,NULL, [
      'value1' => 'name1',
      'value2' => 'name2',
      'value3' => 'name3',
    ], 'testValue'));
  }

  /**
   * Tests the optionsField with description.
   *
   * @return void
   */
  public function testOptionsFieldWithDescription(): void {
    $expected = self::getExpectedOptionFields('test', 'This is a test');
    $expected['description'] = 'Test description';

    $this->assertEquals($expected, $this->optionsField('test', 'This is a test', 0,'Test description',
      [
      'value1' => 'name1',
      'value2' => 'name2',
      'value3' => 'name3',
    ], 'testValue'));
  }

  /**
   * Tests the checkbox method.
   *
   * @return void
   */
  public function testCheckBox(): void {
    $expected = self::getExpectedCheckbox('test', 'testLabel', Conditions::OPTION_YES);
    $this->assertEquals($expected,
      $this->checkbox('test', 'testLabel', 0, 'testValue'));
    $expected['value'] = 'no';
    $this->assertEquals($expected,
      $this->checkbox('test', 'testLabel', 0, ''));
  }

}
