<?php

namespace Drupal\if_then_else\core\Nodes\Actions\GetEntityFieldAction;

use Drupal\if_then_else\core\Nodes\Actions\Action;
use Drupal\if_then_else\Event\NodeSubscriptionEvent;
use Drupal\if_then_else\Event\NodeValidationEvent;
use Drupal\if_then_else\Event\FieldValueProcessEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\if_then_else\core\IfthenelseUtilitiesInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class GetEntityFieldAction.
 *
 * @package Drupal\if_then_else\core\Nodes\Actions\GetEntityFieldAction
 */
class GetEntityFieldAction extends Action
{
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
     * The Date Formatter.
     *
     * @var \Drupal\Core\Datetime\DateFormatterInterface
     */
    protected $dateFormatter;

    /**
     * Constructs a new RouteSubscriber object.
     *
     * @param \Drupal\if_then_else\core\IfthenelseUtilitiesInterface $ifthenelse_utilities
     *   The ifthenelse utitlities.
     * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
     *   The Date Formatter.
     */
    public function __construct(IfthenelseUtilitiesInterface $ifthenelse_utilities, DateFormatterInterface $date_formatter, EventDispatcherInterface $event_dispatcher)
    {
        $this->ifthenelseUtilities = $ifthenelse_utilities;
        $this->dateFormatter = $date_formatter;
        $this->eventDispatcher = $event_dispatcher;
    }

    /**
     * {@inheritDoc}.
     */
    public static function getName()
    {
        return 'get_entity_field_action';
    }

    /**
     * {@inheritDoc}.
     */
    public function registerNode(NodeSubscriptionEvent $event)
    {
        // Fetch all fields for config entity bundles.
        $form_entity_info = $this->ifthenelseUtilities->getContentEntitiesAndBundles();
        $form_fields = $this->ifthenelseUtilities->getFieldsByEntityBundleId($form_entity_info);
        $fields_type = $this->ifthenelseUtilities->getFieldsByEntityBundleId($form_entity_info, 'field_type');
        $fields_cardinality = $this->ifthenelseUtilities->getFieldsByEntityBundleId($form_entity_info, 'field_cardinality');

        $event->nodes[static::getName()] = [
      'label' => $this->t('Get Entity Field Value'),
      'description' => $this->t('Get Entity Field Value'),
      'type' => 'action',
      'class' => 'Drupal\\if_then_else\\core\\Nodes\\Actions\\GetEntityFieldAction\\GetEntityFieldAction',
      'classArg' => ['ifthenelse.utilities', 'date.formatter', 'event_dispatcher'],
      'library' => 'if_then_else/GetEntityFieldAction',
      'control_class_name' => 'GetEntityFieldActionControl',
      'component_class_name' => 'GetEntityFieldActionComponent',
      'entity_info' => $form_entity_info,
      'form_fields' => $form_fields,
      'form_fields_type' => $fields_type,
      'form_fields_cardinality' => $fields_cardinality,
      'inputs' => [
        'entity' => [
          'label' => $this->t('Entity'),
          'description' => $this->t('Entity object.'),
          'sockets' => ['object.entity'],
          'required' => true,
        ],
      ],
      'outputs' => [
        'field_value' => [
          'label' => $this->t('Field Value'),
          'description' => $this->t('Value of the field set in the entity.'),
          'socket' => 'object.field',
        ],
      ],
    ];
    }

    /**
     * Entity field value validation.
     */
    public function validateNode(NodeValidationEvent $event)
    {
        $data = $event->node->data;
        if (!property_exists($data, 'form_fields')) {
            $event->errors[] = $this->t('Select a field name or enter field name in "@node_name".', ['@node_name' => $event->node->name]);
        }

        if (!property_exists($data, 'selected_entity')) {
            $event->errors[] = $this->t('Select an Entity in "@node_name".', ['@node_name' => $event->node->name]);
        }

        if (!property_exists($data, 'selected_bundle')) {
            $event->errors[] = $this->t('Select a Bundle in "@node_name".', ['@node_name' => $event->node->name]);
        }
    }

    /**
     * {@inheritDoc}.
     */
    public function process()
    {
        /** @var \Drupal\Core\Entity\EntityBase $entity */
        $entity = $this->inputs['entity'];
        $form_fields = $this->data->form_fields;

        if (!$entity) {
            $this->setSuccess(false);
            return;
        }

        if ($form_fields->code == 'title') {
            $output = $entity->getTitle();
        } else {
            if ($entity->get($form_fields->code) == null) {
                $this->setSuccess(false);
                return;
            }
            $field_value = $entity->get($form_fields->code)->getValue();
            $field_type = $this->data->field_type;
            $field_cardinality = $this->data->field_cardinality;
            $output = "";

            $field_process_event = new FieldValueProcessEvent($field_value, $field_cardinality, $output);
            // Get the event_dispatcher service and dispatch the event.
            $this->eventDispatcher->dispatch('if_then_else_' . $field_type . '_field_type_process_event', $field_process_event);

            $output = $field_process_event->output;
        }

        if (!isset($output) && empty($output)) {
            $this->setSuccess(false);
            return;
        } else {
            $this->outputs['field_value'] = $output;
        }
    }
}
