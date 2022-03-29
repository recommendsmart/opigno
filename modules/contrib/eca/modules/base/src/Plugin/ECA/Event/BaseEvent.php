<?php

namespace Drupal\eca_base\Plugin\ECA\Event;

use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Event\Tag;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Drupal\eca_base\BaseEvents;
use Drupal\eca_base\Event\CronEvent;
use Drupal\eca_base\Event\CustomEvent;

/**
 * Plugin implementation of the ECA base Events.
 *
 * @EcaEvent(
 *   id = "eca_base",
 *   deriver = "Drupal\eca_base\Plugin\ECA\Event\BaseEventDeriver"
 * )
 */
class BaseEvent extends EventBase {

  /**
   * @return array[]
   */
  public static function actions(): array {
    $actions = [];
    $actions['eca_cron'] = [
      'label' => 'ECA cron event',
      'event_name' => BaseEvents::CRON,
      'event_class' => CronEvent::class,
      'tags' => Tag::RUNTIME | Tag::PERSISTENT | Tag::EPHEMERAL,
    ];
    $actions['eca_custom'] = [
      'label' => 'ECA custom event',
      'event_name' => BaseEvents::CUSTOM,
      'event_class' => CustomEvent::class,
      'tags' => Tag::RUNTIME,
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    if ($this->eventClass() === CronEvent::class) {
      return CronEvent::fields();
    }
    if ($this->eventClass() === CustomEvent::class) {
      return CustomEvent::fields();
    }
    return parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function lazyLoadingWildcard(string $eca_config_id, EcaEvent $ecaEvent): string {
    switch ($this->getDerivativeId()) {

      case 'eca_custom':
        $configuration = $ecaEvent->getConfiguration();
        return isset($configuration['event_id']) ? trim($configuration['event_id']) : '';

      case 'eca_cron':
        return $ecaEvent->getId() . '::' . $ecaEvent->getConfiguration()['frequency'];

      default:
        return parent::lazyLoadingWildcard($eca_config_id, $ecaEvent);

    }
  }

}
