<?php

namespace Drupal\react_calendar\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\react_calendar\CalendarConfigurationInterface;

/**
 * Provides a 'CalendarBlock' block.
 *
 * @Block(
 *  id = "calendar_block",
 *  admin_label = @Translation("Calendar"),
 * )
 */
class CalendarBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\react_calendar\CalendarConfigurationInterface definition.
   *
   * @var \Drupal\react_calendar\CalendarConfigurationInterface
   */
  protected $reactCalendarConfig;

  /**
   * Constructs a new CalendarBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\react_calendar\CalendarConfigurationInterface $react_calendar_config
   *   CalendarConfiguration definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CalendarConfigurationInterface $react_calendar_config
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->reactCalendarConfig = $react_calendar_config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('react_calendar.config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->reactCalendarConfig->getCalendar();
  }

}
