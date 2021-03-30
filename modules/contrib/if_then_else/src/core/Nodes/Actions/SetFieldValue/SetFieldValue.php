<?php

namespace Drupal\if_then_else\core\Nodes\Actions\SetFieldValue;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\if_then_else\core\Nodes\Actions\Action;
use Drupal\if_then_else\Event\NodeSubscriptionEvent;
use Drupal\if_then_else\Event\NodeValidationEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\if_then_else\core\IfthenelseUtilitiesInterface;

/**
 * Class defined to execute make fields required action node.
 */
class SetFieldValue extends Action {
  use StringTranslationTrait;

  /**
   * The ifthenelse utitlities.
   *
   * @var \Drupal\if_then_else\core\IfthenelseUtilitiesInterface
   */
  protected $ifthenelseUtilities;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\if_then_else\core\IfthenelseUtilitiesInterface $ifthenelse_utilities
   *   The ifthenelse utilities.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(IfthenelseUtilitiesInterface $ifthenelse_utilities,
                              EntityTypeManagerInterface $entity_manager) {
    $this->ifthenelseUtilities = $ifthenelse_utilities;
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * Return name of node.
   */
  public static function getName() {
    return 'set_form_field_value_action';
  }

  /**
   * Event subscriber for register set field value node.
   */
  public function registerNode(NodeSubscriptionEvent $event) {
    // Fetch all fields for config entity bundles.
    $form_entity_info = $this->ifthenelseUtilities->getContentEntitiesAndBundles();
    $form_fields = $this->ifthenelseUtilities->getFieldsByEntityBundleId($form_entity_info);
    $fields_type = $this->ifthenelseUtilities->getFieldsByEntityBundleId($form_entity_info, 'field_type');

    $event->nodes[static::getName()] = [
      'label' => $this->t('Set Form Field Value'),
      'description' => $this->t('Set Form Field Value'),
      'type' => 'action',
      'class' => 'Drupal\\if_then_else\\core\\Nodes\\Actions\\SetFieldValue\\SetFieldValue',
      'classArg' => ['ifthenelse.utilities', 'entity_type.manager'],
      'library' => 'if_then_else/SetFieldValue',
      'control_class_name' => 'FieldValueControl',
      'entity_info' => $form_entity_info,
      'form_fields' => $form_fields,
      'form_fields_type' => $fields_type,
      'component_class_name' => 'SetFormFieldValueActionComponent',
      'inputs' => [
        'form' => [
          'label' => $this->t('Form'),
          'description' => $this->t('Form object.'),
          'sockets' => ['form'],
          'required' => TRUE,
        ],
        'field_value' => [
          'label' => $this->t('Field value'),
          'description' => $this->t('Entity for setting field value'),
          'sockets' => ['string', 'number', 'object.entity'],
        ],
      ],
    ];
  }

  /**
   * Validation function.
   */
  public function validateNode(NodeValidationEvent $event) {
    $inputs = $event->node->inputs;
    if (!count($inputs->field_value->connections)) {
      $event->errors[] = $this->t('Provide a value to field value socket in "@node_name".', ['@node_name' => $event->node->name]);
    }
  }

  /**
   * Process function for set default value node.
   */
  public function process() {
    $this->setValueOfField();
  }

  /**
   * Set field value.
   */
  private function setValueOfField() {
    $form_fields = $this->data->form_fields;
    $form = &$this->inputs['form'];
    $field_input = $this->inputs['field_value'];
    $field_type = $this->data->field_type;

    switch ($field_type) {
      case 'string':
      case 'email':
      case 'decimal':
      case 'float':
      case 'integer':
      case 'string_long':
        if (isset($field_input) && !empty($field_input) && $field_input != NULL) {
          $form[$form_fields->code]['widget'][0]['value']['#default_value'] = $field_input;
        }
        break;

      case 'list_float':
      case 'list_integer':
      case 'list_string':
        if (isset($field_input) && !empty($field_input) && $field_input != NULL) {
          $form[$form_fields->code]['widget']['#default_value'][0] = $field_input;
        }
        break;

      case 'text':
      case 'text_long':
        if (isset($field_input) && !empty($field_input) && $field_input != NULL) {
          $form[$form_fields->code]['widget'][0]['#default_value'] = $field_input->value;
          $form[$form_fields->code]['widget'][0]['#format'] = $field_input->format;
        }
        break;

      case 'datetime':
        if (isset($field_input) && !empty($field_input) && $field_input != NULL) {
          $form[$form_fields->code]['widget'][0]['value']['#default_value'] = new DrupalDateTime($field_input);;
        }
        break;

      case 'boolean':
        if (isset($field_input) && !empty($field_input) && $field_input != NULL) {
          $form[$form_fields->code]['widget']['value']['#default_value'] = $field_input;
        }
        break;

      case 'entity_reference':
        if (isset($field_input) && !empty($field_input) && $field_input != NULL) {
          if (isset($form[$form_fields->code]['widget'][0]['target_id'])) {
            if ($form[$form_fields->code]['widget'][0]['target_id']['#target_type'] == 'node') {
              $form[$form_fields->code]['widget'][0]['target_id']['#default_value'] = $this->entityTypeManager->getStorage('node')->load($field_input);
            }
            elseif ($form[$form_fields->code]['widget'][0]['target_id']['#target_type'] == 'taxonomy_term') {
              $form[$form_fields->code]['widget'][0]['target_id']['#default_value'] = $this->entityTypeManager->getStorage('taxonomy_term')->load($field_input);
            }
            elseif ($form[$form_fields->code]['widget'][0]['target_id']['#target_type'] == 'user') {
              $form[$form_fields->code]['widget'][0]['target_id']['#default_value'] = $this->entityTypeManager->getStorage('user')->load($field_input);
            }
          }
          else {
            $form[$form_fields->code]['widget']['target_id']['#default_value'][0] = $this->entityTypeManager->getStorage('taxonomy_term')->load($field_input);
          }
        }
        break;

      case 'text_with_summary':
        if (isset($field_input) && !empty($field_input) && $field_input != NULL) {
          $form[$form_fields->code]['widget'][0]['#default_value'] = $field_input->value;
          $form[$form_fields->code]['widget'][0]['summary']['#default_value'] = $field_input->summary;
          $form[$form_fields->code]['widget'][0]['#format'] = $field_input->format;
        }
        break;

      case 'image':
        if (isset($field_input) && !empty($field_input) && $field_input != NULL) {
          $form[$form_fields->code]['widget'][0]['#default_value']['target_id'] = $field_input->fids;
          $form[$form_fields->code]['widget'][0]['#default_value']['alt'] = $field_input->alt;
          $form[$form_fields->code]['widget'][0]['#default_value']['title'] = $field_input->title;
          $form[$form_fields->code]['widget'][0]['#default_value']['fids'][0] = $field_input->fids;
          $form[$form_fields->code]['widget'][0]['#default_value']['description'] = $field_input->description;
          $form[$form_fields->code]['widget'][0]['#default_value']['width'] = $field_input->width;
          $form[$form_fields->code]['widget'][0]['#default_value']['height'] = $field_input->height;
        }
        break;

      case 'link':
        if (isset($field_input) && !empty($field_input) && $field_input != NULL) {
          $form[$form_fields->code]['widget'][0]['uri']['#default_value'] = $field_input->uri;
          $form[$form_fields->code]['widget'][0]['title']['#default_value'] = $field_input->title;
        }
        break;
    }
  }

}
