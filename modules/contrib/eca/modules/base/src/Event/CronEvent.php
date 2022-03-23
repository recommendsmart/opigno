<?php

namespace Drupal\eca_base\Event;

use Cron\CronExpression;
use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\EcaState;
use Drupal\eca\Event\ConditionalApplianceInterface;

/**
 * Class CronEvent
 *
 * @package Drupal\eca_base\Event
 */
class CronEvent extends Event implements ConditionalApplianceInterface {

  /**
   * @var \Drupal\eca\EcaState
   */
  protected EcaState $store;

  /**
   * Provides field specifications for the modeller.
   *
   * @return string[]
   */
  public static function fields(): array {
    return [[
      'name' => 'frequency',
      'label' => 'Frequency (UTC)',
      'type' => 'String',
    ]];
  }

  /**
   * {@inheritdoc}
   *
   * Verifies if this event is due for the next execution.
   *
   * This event stores the last execution time for each modeller event
   * identified by $id and determines with the given frequency, if and when
   * this same event triggered cron should be executed.
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    [$id, $frequency] = explode('::', $wildcard, 2);
    return $this->isDue($id, $frequency);
  }

  /**
   * {@inheritdoc}
   *
   * When this event is due for next execution, this also stores the current
   * time as the new "last executed" timestamp in the ECA state.
   */
  public function applies(string $id, array $arguments): bool {
    if ($this->isDue($id, $arguments['frequency'])) {
      $this->storeTimestamp($id);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Initializes the ECA key value store service once and returns it.
   *
   * @return \Drupal\eca\EcaState
   *   The ECA key value store.
   */
  protected function keyValueStore(): EcaState {
    if (!isset($this->store)) {
      $this->store = \Drupal::service('eca.state');
    }
    return $this->store;
  }

  /**
   * Determines, if the cron with $id is due for next execution.
   *
   * It receives the last execution time of this event cron and calculates
   * by the given frequency, if the next execution time has already been
   * passed and returns TRUE, if so.
   *
   * @param string $id
   *   The id of the modeller event.
   * @param string $frequency
   *   The frequency as a cron pattern.
   *
   * @return bool
   *   TRUE, if the event $id is due for next execution, FALSE otherwise.
   */
  protected function isDue(string $id, string $frequency): bool {
    $cron = new CronExpression($frequency);
    $lastRun = $this->keyValueStore()->getTimestamp('cron-' . $id);
    $dt = new \DateTime();
    $dt
      ->setTimezone(new \DateTimeZone('UTC'))
      ->setTimestamp($lastRun);
    try {
      return $this->keyValueStore()->getCurrentTimestamp() > $cron->getNextRunDate($dt)->getTimestamp();
    }
    catch (\Exception $e) {
      // @todo: Log this exception.
    }
    return FALSE;
  }

  /**
   * Stores the execution time for the modeller event $id in ECA state.
   *
   * @param string $id
   *   The id of the modeller event.
   */
  protected function storeTimestamp(string $id): void {
    $this->keyValueStore()->setTimestamp('cron-' . $id);
  }

}
