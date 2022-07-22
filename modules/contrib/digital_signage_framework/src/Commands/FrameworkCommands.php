<?php

namespace Drupal\digital_signage_framework\Commands;

use Drupal\digital_signage_framework\Entity\Device;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile for digital signage framework.
 */
class FrameworkCommands extends DrushCommands {

  /**
   * Enable monitoring.
   *
   * @usage digital-signage-framework:monitoring:enable
   *   Enable monitoring.
   *
   * @command digital-signage-framework:monitoring:enable
   * @aliases dsdme
   */
  public function enableMonitoring(): void {
    \Drupal::state()->set('digital-signage-framework:monitoring:status', 'on');
  }

  /**
   * Disable monitoring.
   *
   * @usage digital-signage-framework:monitoring:disable
   *   Disable monitoring.
   *
   * @command digital-signage-framework:monitoring:disable
   * @aliases dsdmd
   */
  public function disableMonitoring(): void {
    \Drupal::state()->set('digital-signage-framework:monitoring:status', 'off');
  }

  /**
   * Monitor slide change.
   *
   * @usage digital-signage-framework:monitoring:slidechange
   *   Monitor slide change.
   *
   * @command digital-signage-framework:monitoring:slidechange
   * @aliases dsdmsc
   */
  public function monitorSlideChange(): void {
    $debug = $this->io()->isVerbose();
    if (\Drupal::state()->get('digital-signage-framework:monitoring:status', 'on') !== 'on') {
      if ($debug) {
        $this->io()->writeln('Monitoring is disabled.');
      }
      return;
    }
    /** @var \Drupal\Core\Logger\LoggerChannelInterface $logger */
    $logger = \Drupal::service('digital_signage_platform.logger');
    foreach (Device::loadMultiple() as $device) {
      if ($debug) {
        $this->io()->writeln('Device: ' . $device->label());
      }
      if (!$device->isEnabled()) {
        if ($debug) {
          $this->io()->writeln('...disabled.');
        }
        continue;
      }
      $state = \Drupal::state()->get('digital-signage-device:' . $device->id() . ':status:slide', 'ok');
      $newState = 'ok';
      $schedule = $device->getSchedule();
      if ($schedule && count($schedule->getItems()) > 1) {
        $rows = $device->getPlugin()->showSlideReport($device);
        if (empty($rows)) {
          $newState = 'empty';
        }
      }
      if ($debug) {
        $this->io()->writeln('... ' . $state . '/' . $newState);
      }
      if ($newState !== $state || $newState === 'empty') {
        if ($newState === 'ok') {
          $logger->info('Slide change on device %name restored.', ['%name' => $device->label()]);
        }
        else {
          $logger->critical('Slide change on device %name broken.', ['%name' => $device->label()]);
        }
        \Drupal::state()->set('digital-signage-device:' . $device->id() . ':status:slide', $newState);
      }
    }
  }

}
