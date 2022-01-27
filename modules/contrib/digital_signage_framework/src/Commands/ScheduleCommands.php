<?php

namespace Drupal\digital_signage_framework\Commands;

use Drupal\digital_signage_framework\ScheduleManager;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile for digital signage devices.
 */
class ScheduleCommands extends DrushCommands {

  /**
   * @var \Drupal\digital_signage_framework\ScheduleManager
   */
  protected $scheduleManager;

  /**
   * DigitalSignageScheduleCommands constructor.
   *
   * @param \Drupal\digital_signage_framework\ScheduleManager $schedule_manager
   */
  public function __construct(ScheduleManager $schedule_manager) {
    $this->scheduleManager = $schedule_manager;
    parent::__construct();
  }

  /**
   * Command to push schedules and configuration to devices.
   *
   * @usage digital-signage-schedule:push
   *   Pushes schedules and configuration to devices.
   *
   * @command digital-signage-schedule:push
   * @option device
   * @option force
   * @option debugmode
   * @option reloadassets
   * @option reloadcontent
   * @option entitytype
   * @option entityid
   * @aliases dssp
   *
   * @param array $options
   */
  public function pushSchedules(array $options = ['device' => NULL, 'force' => FALSE, 'debugmode' => FALSE, 'reloadassets' => FALSE, 'reloadcontent' => FALSE, 'entitytype' => NULL, 'entityid' => NULL]) {
    if ($options['entitytype'] !== NULL && $options['entityid'] === NULL) {
      $this->io()->error('Entity ID is required if entity type is given.');
      return;
    }
    $this->scheduleManager->pushSchedules($options['device'], $options['force'], $options['debugmode'], $options['reloadassets'], $options['reloadcontent'], $options['entitytype'], $options['entityid']);
  }

  /**
   * Command to temporarily push updated configuration to devices.
   *
   * @usage digital-signage-schedule:config
   *   Push updated configuration to devices.
   *
   * @command digital-signage-schedule:config
   * @option device
   * @option debugmode
   * @option reloadschedule
   * @option reloadassets
   * @option reloadcontent
   * @aliases dssc
   *
   * @param array $options
   */
  public function pushConfiguration(array $options = ['device' => NULL, 'debugmode' => FALSE, 'reloadschedule' => FALSE, 'reloadassets' => FALSE, 'reloadcontent' => FALSE]) {
    $this->scheduleManager->pushConfiguration($options['device'], $options['debugmode'], $options['reloadschedule'], $options['reloadassets'], $options['reloadcontent']);
  }

}
