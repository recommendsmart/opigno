<?php

namespace Drupal\react_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\react_calendar\CalendarConfigurationInterface;

/**
 * Class CalendarController.
 */
class CalendarController extends ControllerBase {

  /**
   * Drupal\react_calendar\CalendarConfigurationInterface definition.
   *
   * @var \Drupal\react_calendar\CalendarConfigurationInterface
   */
  protected $reactCalendarConfig;

  /**
   * Constructs a new CalendarController object.
   */
  public function __construct(CalendarConfigurationInterface $react_calendar_config) {
    $this->reactCalendarConfig = $react_calendar_config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('react_calendar.config')
    );
  }

  /**
   * Returns a React Calendar.
   *
   * @return array
   *   Return calendar render array.
   */
  public function calendar() {
    return $this->reactCalendarConfig->getCalendar();
  }

}
