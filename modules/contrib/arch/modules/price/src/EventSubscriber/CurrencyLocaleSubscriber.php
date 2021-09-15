<?php

namespace Drupal\arch_price\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\currency\Event\ResolveCountryCode;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Currency locale subscriber.
 *
 * @package Drupal\arch_price\EventSubscriber
 */
class CurrencyLocaleSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory
  ) {
    $this->configFactory = $config_factory;
  }

  /**
   * On resolv country code event.
   *
   * @param \Drupal\currency\Event\ResolveCountryCode $event
   *   Event.
   * @param string $event_name
   *   Event name.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   Dispatcher.
   */
  public function onResolveCountryCode(ResolveCountryCode $event, $event_name, EventDispatcherInterface $dispatcher) {
    $country_code = $this->configFactory->get('system.date')->get('country.default');
    if ($country_code) {
      $event->setCountryCode($country_code);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'drupal.currency.resolve_country_code' => 'onResolveCountryCode',
    ];
  }

}
