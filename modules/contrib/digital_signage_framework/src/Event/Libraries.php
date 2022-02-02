<?php

namespace Drupal\digital_signage_framework\Event;

use Drupal\digital_signage_framework\DeviceInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class Libraries
 *
 * @package Drupal\digital_signage_framework\Event
 */
class Libraries extends Event {

  /**
   * @var array
   */
  protected $libraries = [];

  /**
   * @var array
   */
  protected $settings = [];

  /**
   * @var \Drupal\digital_signage_framework\DeviceInterface
   */
  protected $device;

  /**
   * Libraries constructor.
   *
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   */
  public function __construct(DeviceInterface $device) {
    $this->device = $device;
  }

  /**
   * @return array
   */
  public function getLibraries(): array {
    return $this->libraries;
  }

  /**
   * @return array
   */
  public function getSettings(): array {
    return $this->settings;
  }

  /**
   * @param string $library
   *
   * @return \Drupal\digital_signage_framework\Event\Libraries
   */
  public function addLibrary($library): Libraries {
    $this->libraries[] = $library;
    return $this;
  }

  /**
   * @param string $module
   * @param array $settings
   *
   * @return \Drupal\digital_signage_framework\Event\Libraries
   */
  public function addSettings(string $module, array $settings): Libraries {
    $this->settings[$module] = $settings;
    return $this;
  }

  /**
   * @return \Drupal\digital_signage_framework\DeviceInterface
   */
  public function getDevice(): DeviceInterface {
    return $this->device;
  }

}
