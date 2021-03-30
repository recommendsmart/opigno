<?php

namespace Drupal\if_then_else\core\Nodes\Actions\GetFieldValue;

use Drupal\if_then_else\core\Nodes\Actions\Action;
use Drupal\if_then_else\Event\NodeSubscriptionEvent;
use Drupal\if_then_else\Event\GraphValidationEvent;
use Drupal\if_then_else\Event\NodeValidationEvent;
use Drupal\if_then_else\Event\FieldValueProcessEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\if_then_else\core\IfthenelseUtilitiesInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class defined to get value of entity field.
 */
class GetFieldValue extends Action {
  use StringTranslationTrait;
  /**
   * The ifthenelse utitlities.
   *
   * @var \Drupal\if_then_else\core\IfthenelseUtilitiesInterface
   */
  protected $ifthenelseUtilities;

  /**
   * The Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\if_then_else\core\IfthenelseUtilitiesInterface $ifthenelse_utilities
   *   The ifthenelse utitlities.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The entityTypeManager.
   */
  public function __construct(IfthenelseUtilitiesInterface $ifthenelse_utilities, EventDispatcherInterface $event_dispatcher) {
    $this->ifthenelseUtilities = $ifthenelse_utilities;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Return name of node.
   */
  public static function getName() {
    return 'get_form_field_value_action';
  }

  /**
   * Event subscriber for register get field value.
   */
  public function registerNode(NodeSubscriptionEvent $event) {
    // Fetch all fields for config entity bundles.
    $form_entity_info = $this->ifthenelseUtilities->getContentEntitiesAndBundles();
    $form_fields = $this->ifthenelseUtilities->getFieldsByEntityBundleId($form_entity_info);
    $fields_type = $this->ifthenelseUtilities->getFieldsByEntityBundleId($form_entity_info, 'field_type');
    $fields_cardinality = $this->ifthenelseUtilities->getFieldsByEntityBundleId($form_entity_info, 'field_cardinality');

    $event->nodes[static::getName()] = [
      'label' => $this->t('Get Form Field Value'),
      'description' => $this->t('Get Form Field Value'),
      'type' => 'action',
      'class' => 'Drupal\\if_then_else\\core\\Nodes\\Actions\\GetFieldValue\\GetFieldValue',
      'classArg' => ['ifthenelse.utilities', 'event_dispatcher'],
      'library' => 'if_then_else/GetFieldValue',
      'control_class_name' => 'GetFieldValueControl',
      'entity_info' => $form_entity_info,
      'form_fields' => $form_fields,
      'form_fields_type' => $fields_type,
      'form_fields_cardinality' => $fields_cardinality,
      'component_class_name' => 'GetFieldValueActionComponent',
      'inputs' => [
        'form_state' => [
          'label' => $this->t('Form State'),
          'description' => $this->t('Form state object.'),
          'sockets' => ['form_state'],
          'required' => TRUE,
        ],
      ],
      'outputs' => [
        'field_value' => [
          'label' => $this->t('Field Value'),
          'description' => $this->t('Field Value'),
          'socket' => 'object.field',
        ],
      ],
    ];
  }

  /**
   * Validate Graph.
   */
  public function validateGraph(GraphValidationEvent $event) {
    $nodes = $event->data->nodes;

    foreach ($nodes as $node) {
      if ($node->data->type == 'event' && $node->data->name != 'form_validate_event') {
        $event->errors[] = $this->t('Get Value of field will only work with Form validate Event');
      }
    }
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
    $this->getValueOfField();
  }

  /**
   * Get field value.
   */
  private function getValueOfField() {
    $form_state = $this->inputs['form_state'];
    $form_fields = $this->data->form_fields;

    if ($form_fields->code == 'title') {
      $field_value = $form_state->getValue($form_fields->code);
      $output = $field_value[0]['value'];
    }
    else {
      $field_value = $form_state->getValue($form_fields->code);
      $field_type = $this->data->field_type;
      $field_cardinality = $this->data->field_cardinality;
      $output = "";

      $field_process_event = new FieldValueProcessEvent($field_value, $field_cardinality, $output);
      // Get the event_dispatcher service and dispatch the event.
      $this->eventDispatcher->dispatch('if_then_else_' . $field_type . '_field_type_process_event', $field_process_event);

      $output = $field_process_event->output;
    }

    if (!isset($output) && empty($output)) {
      $this->setSuccess(FALSE);
      return;
    }
    else {
      $this->outputs['field_value'] = $output;
    }
  }

}
