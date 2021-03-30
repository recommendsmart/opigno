<?php

namespace Drupal\if_then_else\core\Nodes\Values\DateTimeValue;

use Drupal\if_then_else\core\Nodes\Values\Value;
use Drupal\if_then_else\Event\NodeSubscriptionEvent;
use Drupal\if_then_else\Event\NodeValidationEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Textvalue node class.
 */
class DateTimeValue extends Value {
  use StringTranslationTrait;

  /**
   * The Date Formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The Date Formatter.
   */
  public function __construct(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Return name of node.
   */
  public static function getName() {
    return 'date_time_value';
  }

  /**
   * Event subscriber of registering node.
   */
  public function registerNode(NodeSubscriptionEvent $event) {
    $event->nodes[static::getName()] = [
      'label' => $this->t('Date And Time'),
      'description' => $this->t('Date And Time'),
      'type' => 'value',
      'class' => 'Drupal\\if_then_else\\core\\Nodes\\Values\\DateTimeValue\\DateTimeValue',
      'library' => 'if_then_else/DateTimeValue',
      'control_class_name' => 'DateTimeValueControl',
      'component_class_name' => 'DateTimeValueComponent',
      'classArg' => ['date.formatter'],
      'outputs' => [
        'datetime' => [
          'label' => $this->t('Date Time'),
          'description' => $this->t('Date Time String'),
          'socket' => 'string',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateNode(NodeValidationEvent $event) {
    $data = $event->node->data;

    if (!property_exists($data, 'selection')) {
      $event->errors[] = $this->t('Select the option in "@node_name".', ['@node_name' => $event->node->name]);
      return;
    }
    if (!property_exists($data, 'outputValue')) {
      $event->errors[] = $this->t('Select the option in "@node_name".', ['@node_name' => $event->node->name]);
      return;
    }
  }

  /**
   * Process function for DateTimeValue node.
   */
  public function process() {
    $outputValue = $this->data->outputValue;
    $date = strtotime($this->data->value);
    if ($outputValue == 'current') {
      $date = $this->dateFormatter->format($date, 'custom', 'Y-m-d H:i:s', drupal_get_user_timezone());
      $date = str_replace(' ', 'T', trim($date));
    }
    elseif ($outputValue == 'fixed') {
      $date = $this->dateFormatter->format(time(), 'custom', 'Y-m-d H:i:s', drupal_get_user_timezone());
      $date = str_replace(' ', 'T', trim($date));
    }
    elseif ($outputValue == 'plusoffset') {
      $offset = $this->data->valueText;
      $date = strtotime('+' . $offset, $date);
      $date = $this->dateFormatter->format($date, 'custom', 'Y-m-d H:i:s', drupal_get_user_timezone());
      $date = str_replace(' ', 'T', trim($date));
    }
    elseif ($outputValue == 'minusoffset') {
      $offset = $this->data->valueText;
      $date = strtotime($date . '-' . $offset);
      $date = $this->dateFormatter->format($date, 'custom', 'Y-m-d H:i:s', drupal_get_user_timezone());
      $date = str_replace(' ', 'T', trim($date));
    }
    if ($this->data->selection == 'string') {
      // Using the storage controller.
      $this->outputs['datetime'] = $date;
    }
    else {
      $this->outputs['datetime'] = strtotime($date);
    }
  }

}
