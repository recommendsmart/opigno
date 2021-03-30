<?php

namespace Drupal\if_then_else\core\Nodes\Values\TextValue;

use Drupal\if_then_else\core\Nodes\Values\Value;
use Drupal\if_then_else\Event\NodeSubscriptionEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Textvalue node class.
 */
class TextValue extends Value {
  use StringTranslationTrait;

  /**
   * Return name of node.
   */
  public static function getName() {
    return 'text_value';
  }

  /**
   * Event subscriber of registering node.
   */
  public function registerNode(NodeSubscriptionEvent $event) {
    $event->nodes[static::getName()] = [
      'label' => $this->t('Text'),
      'description' => $this->t('Text'),
      'type' => 'value',
      'class' => 'Drupal\\if_then_else\\core\\Nodes\\Values\\TextValue\\TextValue',
      'library' => 'if_then_else/TextValue',
      'control_class_name' => 'TextValueControl',
      'inputs' => [
        'input1' => [
          'label' => $this->t('Input 1'),
          'description' => $this->t('Use token {{input1}} to use this text.'),
          'sockets' => ['string', 'number'],
        ],
        'input2' => [
          'label' => $this->t('Input 2'),
          'description' => $this->t('Use token {{input2}} to use this text.'),
          'sockets' => ['string', 'number'],
        ],
        'input3' => [
          'label' => $this->t('Input 3'),
          'description' => $this->t('Use token {{input3}} to use this text.'),
          'sockets' => ['string', 'number'],
        ],
        'input4' => [
          'label' => $this->t('Input 4'),
          'description' => $this->t('Use token {{input4}} to use this text.'),
          'sockets' => ['string', 'number'],
        ],
        'input5' => [
          'label' => $this->t('Input 5'),
          'description' => $this->t('Use token {{input5}} to use this text.'),
          'sockets' => ['string', 'number'],
        ],
      ],
      'outputs' => [
        'text' => [
          'label' => $this->t('Output'),
          'description' => $this->t('Output text.'),
          'socket' => 'string',
        ],
      ],
    ];
  }

  /**
   * Process function for Textvalue node.
   */
  public function process() {
    $text = $this->data->value;
    if (isset($this->inputs['input1'])) {
      $text = str_replace('{{input1}}', $this->inputs['input1'], $text);
    }

    if (isset($this->inputs['input2'])) {
      $text = str_replace('{{input2}}', $this->inputs['input2'], $text);
    }

    if (isset($this->inputs['input3'])) {
      $text = str_replace('{{input3}}', $this->inputs['input3'], $text);
    }

    if (isset($this->inputs['input4'])) {
      $text = str_replace('{{input4}}', $this->inputs['input4'], $text);
    }

    if (isset($this->inputs['input5'])) {

      $text = str_replace('{{input5}}', $this->inputs['input5'], $text);
    }

    $this->outputs['text'] = $text;
  }

}
