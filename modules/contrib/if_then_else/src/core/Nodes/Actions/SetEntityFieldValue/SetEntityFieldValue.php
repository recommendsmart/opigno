<?php

namespace Drupal\if_then_else\core\Nodes\Actions\SetEntityFieldValue;

use Drupal\if_then_else\core\Nodes\Actions\Action;
use Drupal\if_then_else\Event\NodeSubscriptionEvent;
use Drupal\if_then_else\Event\NodeValidationEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\if_then_else\core\IfthenelseUtilitiesInterface;

/**
 * Class defined to set value of entity field.
 */
class SetEntityFieldValue extends Action {
  use StringTranslationTrait;

  /**
   * The ifthenelse utitlities.
   *
   * @var \Drupal\if_then_else\core\IfthenelseUtilitiesInterface
   */
  protected $ifthenelseUtilities;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\if_then_else\core\IfthenelseUtilitiesInterface $ifthenelse_utilities
   *   The ifthenelse utitlities.
   */
  public function __construct(IfthenelseUtilitiesInterface $ifthenelse_utilities) {
    $this->ifthenelseUtilities = $ifthenelse_utilities;
  }

  /**
   * Return name of node.
   */
  public static function getName() {
    return 'set_entity_field_value_action';
  }

  /**
   * Event subscriber for register set entit field value.
   */
  public function registerNode(NodeSubscriptionEvent $event) {
    // Fetch all fields for config entity bundles.
    $form_entity_info = $this->ifthenelseUtilities->getContentEntitiesAndBundles();
    $form_fields = $this->ifthenelseUtilities->getFieldsByEntityBundleId($form_entity_info);
    $fields_type = $this->ifthenelseUtilities->getFieldsByEntityBundleId($form_entity_info, 'field_type');

    $event->nodes[static::getName()] = [
      'label' => $this->t('Set Entity Field Value'),
      'description' => $this->t('Set Entity Field Value'),
      'type' => 'action',
      'class' => 'Drupal\\if_then_else\\core\\Nodes\\Actions\\SetEntityFieldValue\\SetEntityFieldValue',
      'classArg' => ['ifthenelse.utilities'],
      'library' => 'if_then_else/SetEntityFieldValue',
      'control_class_name' => 'SetEntityFieldValueControl',
      'entity_info' => $form_entity_info,
      'form_fields' => $form_fields,
      'form_fields_type' => $fields_type,
      'component_class_name' => 'SetEntityFieldValueActionComponent',
      'inputs' => [
        'field_value' => [
          'label' => $this->t('Field Value'),
          'description' => $this->t('Field value to set.'),
          'sockets' => [
            'string',
            'string.url',
            'bool',
            'number',
            'object.field.text_with_summary',
            'object.field.image',
            'object.field.link',
            'object.field.text_long',
          ],
          'required' => TRUE,
        ],
        'entity' => [
          'label' => $this->t('Entity'),
          'description' => $this->t('Entity object'),
          'sockets' => ['object.entity'],
          'required' => TRUE,
        ],
      ],
      'outputs' => [
        'entity' => [
          'label' => $this->t('Entity'),
          'description' => $this->t('Entity object'),
          'socket' => 'object.entity',
        ],
      ],
    ];
  }

  /**
   * Validation function.
   */
  public function validateNode(NodeValidationEvent $event) {
    $data = $event->node->data;
    if (empty($data->form_fields)) {
      $event->errors[] = $this->t('Select a field name to fetch it\' value in "@node_name".', ['@node_name' => $event->node->name]);
    }
  }

  /**
   * Process function to fetch value of field.
   */
  public function process() {
    $this->setValueOfField();
  }

  /**
   * Set field value.
   */
  private function setValueOfField() {
    $field_value = $this->inputs['field_value'];
    $entity = $this->inputs['entity'];
    $field_name = $this->data->form_fields->code;
    $field_type = $this->data->field_type;

    if ($field_name == 'title') {
      if ($entity->getEntityTypeId() == 'node') {
        $entity->setTitle($field_value);
      }
      else {
        $entity->setLabel($field_value);
      }
    }
    else {
      switch ($field_type) {
        case 'list_string':
        case 'string':
        case 'list_integer':
        case 'email':
        case 'list_float':
        case 'list_integer':
        case 'decimal':
        case 'float':
        case 'integer':
        case 'string_long':
        case 'boolean':
          $entity->set($field_name, $field_value);
          break;

        case 'datetime':
          // /$entity->set($field_name,date('c', $field_value->getPhpDateTime()->getTimestamp()));
          break;

        case 'text_with_summary':
          $entity->{$field_name}->setValue(['value' => $field_value->value, 'format' => $field_value->format, 'summary' => $field_value->summary]);
          break;

        case 'text_long':
        case 'text':
          $entity->{$field_name}->setValue(['value' => $field_value->value, 'format' => $field_value->format]);
          break;

        case 'entity_reference':
          $entity->{$field_name}->target_id = $field_value;
          break;

        case 'link':
          $entity->{$field_name}->setValue(['uri' => $field_value->uri, 'title' => $field_value->title]);
          break;

      }
    }

    $this->outputs['entity'] = $entity;
  }

}
