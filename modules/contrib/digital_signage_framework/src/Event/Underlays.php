<?php

namespace Drupal\digital_signage_framework\Event;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\digital_signage_framework\DeviceInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class Underlays
 *
 * @package Drupal\digital_signage_framework\Event
 */
class Underlays extends Event {

  /**
   * @var array
   */
  protected $underlays = [];

  /**
   * @var array
   */
  protected $libraries = [];

  /**
   * @var \Drupal\digital_signage_framework\DeviceInterface
   */
  protected $device;

  /**
   * Rendered constructor.
   *
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   */
  public function __construct(DeviceInterface $device) {
    $this->device = $device;
  }

  /**
   * @return \Drupal\digital_signage_framework\DeviceInterface
   */
  public function getDevice(): DeviceInterface {
    return $this->device;
  }

  /**
   * @return array
   */
  public function getUnderlays(): array {
    $result = [];
    foreach ($this->underlays as $underlay) {
      $result[] = $underlay['markup'];
    }
    return $result;
  }

  /**
   * @return array
   */
  public function getLibraries(): array {
    return $this->libraries;
  }

  /**
   * @param string $id
   * @param string|TranslatableMarkup $label
   * @param string $markup
   * @param array $attached
   *
   * @return \Drupal\digital_signage_framework\Event\Underlays
   */
  public function addUnderlay(string $id, $label, string $markup, array $attached): Underlays {
    $this->underlays[] = [
      'id' => $id,
      'label' => $label,
      'markup' => $markup,
    ];
    if (isset($attached['library'])) {
      foreach ($attached['library'] as $library) {
        $this->libraries[] = $library;
      }
    }
    return $this;
  }

}
