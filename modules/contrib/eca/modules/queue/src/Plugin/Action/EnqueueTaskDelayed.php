<?php

namespace Drupal\eca_queue\Plugin\Action;

use Drupal\eca\Plugin\OptionsInterface;

/**
 * Enqueue a Task with a delay.
 *
 * @Action(
 *   id = "eca_enqueue_task_delayed",
 *   label = @Translation("Enqueue a task with a delay")
 * )
 */
class EnqueueTaskDelayed extends EnqueueTask implements OptionsInterface {

  public const DELAY_SECONDS = 1;
  public const DELAY_MINUTES = 60;
  public const DELAY_HOURS = 3600;
  public const DELAY_DAYS = 86400;
  public const DELAY_WEEKS = 604800;
  public const DELAY_MONTHS = 2592000;

  /**
   * {@inheritdoc}
   */
  protected function getEarliestProcessingTime(): int {
    return \Drupal::time()->getRequestTime() +
      (int) $this->tokenServices->replaceClear($this->configuration['delay_value']) * (int) $this->configuration['delay_unit'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'delay_value' => '1',
      'delay_unit' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(string $id): ?array {
    if ($id === 'delay_unit') {
      return [
        static::DELAY_SECONDS => $this->t('seconds'),
        static::DELAY_MINUTES => $this->t('minutes'),
        static::DELAY_HOURS => $this->t('hours'),
        static::DELAY_DAYS => $this->t('days'),
        static::DELAY_WEEKS => $this->t('weeks'),
        static::DELAY_MONTHS => $this->t('months'),
      ];
    }
    return NULL;
  }

}
