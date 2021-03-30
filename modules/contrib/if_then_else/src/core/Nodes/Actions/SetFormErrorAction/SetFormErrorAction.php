<?php

namespace Drupal\if_then_else\core\Nodes\Actions\SetFormErrorAction;

use Drupal\if_then_else\core\Nodes\Actions\Action;
use Drupal\if_then_else\Event\GraphValidationEvent;
use Drupal\if_then_else\Event\NodeSubscriptionEvent;
use Drupal\if_then_else\Event\NodeValidationEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\if_then_else\core\IfthenelseUtilitiesInterface;

/**
 * Class defined to set form error action node.
 */
class SetFormErrorAction extends Action {
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
    return 'set_form_error_action';
  }

  /**
   * {@inheritdoc}
   */
  public function registerNode(NodeSubscriptionEvent $event) {
    // Fetch all fields for config entity bundles.
    $form_entity_info = $this->ifthenelseUtilities->getContentEntitiesAndBundles();
    $form_fields = $this->ifthenelseUtilities->getFieldsByEntityBundleId($form_entity_info ,'all');

    $event->nodes[static::getName()] = [
      'label' => $this->t('Set Form Error'),
      'description' => $this->t('Set Form Error'),
      'type' => 'action',
      'class' => 'Drupal\\if_then_else\\core\\Nodes\\Actions\\SetFormErrorAction\\SetFormErrorAction',
      'classArg' => ['ifthenelse.utilities'],
      'library' => 'if_then_else/SetFormErrorAction',
      'control_class_name' => 'SetFormErrorActionControl',
      'form_fields' => $form_fields,
      'inputs' => [
        'form_state' => [
          'label' => $this->t('Form State'),
          'description' => $this->t('Form state object.'),
          'sockets' => ['form_state'],
          'required' => TRUE,
        ],
        'message' => [
          'label' => $this->t('Error message'),
          'description' => $this->t('Message for form state.'),
          'sockets' => ['string'],
          'required' => TRUE,
        ],
      ],
    ];
  }

  /**
   * Validation for make fields required action node.
   */
  public function validateNode(NodeValidationEvent $event) {
    // Make sure that form_fields array is not empty.
    if (!count($event->node->data->form_fields)) {
      $event->errors[] = $this->t('Select at least one field in "@node_name".', ['@node_name' => $event->node->name]);
    }
  }

  /**
   * {@inheritDoc}.
   */
  public function validateGraph(GraphValidationEvent $event) {
    $nodes = $event->data->nodes;

    foreach ($nodes as $node) {
      if ($node->data->type == 'event' && $node->data->name != 'form_validate_event') {
        $event->errors[] = $this->t('Set Form Error will only work with Form validate Event');
      }
    }
  }

  /**
   * {@inheritDoc}.
   */
  public function process() {

    $form_state = $this->inputs['form_state'];
    $message = $this->inputs['message'];
    $form_fields = $this->data->form_fields;

    $form_state->setErrorByName($form_fields[0]->code, $message);

  }

}
