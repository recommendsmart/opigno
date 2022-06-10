<?php

namespace Drupal\eca_log\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Event\ConfigurableEventInterface;

/**
 * Provides an event when a log message is being submitted.
 *
 * @package Drupal\eca_log\Event
 */
class LogMessageEvent extends Event implements ConditionalApplianceInterface, ConfigurableEventInterface {

  /**
   * Log message severity.
   *
   * @var int
   */
  protected int $severity;

  /**
   * Log message.
   *
   * @var string
   */
  protected string $message;

  /**
   * Log message context.
   *
   * @var array
   */
  protected array $context;

  /**
   * Construct a LogMessageEvent.
   *
   * @param int $severity
   *   Log message severity.
   * @param string $message
   *   Log message.
   * @param array $context
   *   Log message context.
   */
  public function __construct(int $severity, string $message, array $context) {
    $this->severity = $severity;
    $this->message = $message;
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public static function fields(): array {
    return [
      [
        'name' => 'channel',
        'label' => 'Type',
        'type' => 'String',
      ],
      [
        'name' => 'min_severity',
        'label' => 'Minimum severity',
        'type' => 'Dropdown',
        'extras' => [
          'choices' => self::severities(),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    return (($arguments['channel'] === '') || ($arguments['channel'] === $this->context['channel'])) && $this->severity <= $arguments['min_severity'];
  }

  /**
   * Prepare log levels for drop down fields.
   *
   * @return array
   *   The list of log levels with severity value and name.
   */
  protected static function severities(): array {
    $severities = [];
    foreach (RfcLogLevel::getLevels() as $level => $label) {
      $severities[] = [
        'name' => $label,
        'value' => (string) $level,
      ];
    }
    return $severities;
  }

}
