<?php

namespace Drupal\Tests\eca_form\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\eca\Plugin\ECA\Condition\StringComparisonBase;
use Drupal\eca\PluginManager\Condition;
use Drupal\eca_form\Event\FormBuild;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for the "eca_form_field_value" condition plugin.
 *
 * @group eca
 * @group eca_form
 */
class CompareFormValueTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'eca',
    'eca_form',
  ];

  /**
   * ECA condition plugin manager.
   *
   * @var \Drupal\eca\PluginManager\Condition|null
   */
  protected ?Condition $conditionManager;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->conditionManager = \Drupal::service('plugin.manager.eca.condition');
  }

  /**
   * Tests form field comparison.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testFormField(): void {
    $config = [
      'field_name' => 'test_field',
      'field_value' => 'Test value',
      'operator' => StringComparisonBase::COMPARE_EQUALS,
      'type' => StringComparisonBase::COMPARE_TYPE_VALUE,
      'case' => FALSE,
      'negate' => FALSE,
    ];
    /** @var \Drupal\eca_form\Plugin\ECA\Condition\FormFieldValue $condition */
    $condition = $this->conditionManager->createInstance('eca_form_field_value', $config);
    $form_state = new FormState();
    $form_state->setValue('test_field', 'Test value');
    $form = [];
    $event = new FormBuild($form, $form_state, 'test_id');
    $condition->setEvent($event);
    $this->assertTrue($condition->evaluate(), 'Value of form field "test_field" equals expected value.');
  }

}
