<?php

namespace Drupal\digital_signage_framework\Event;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\digital_signage_framework\DeviceInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class Overlays
 *
 * @package Drupal\digital_signage_framework\Event
 */
class Overlays extends Event {

  /**
   * @var array
   */
  protected $overlays = [];

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
  public function getOverlays(): array {
    $result = [];
    foreach ($this->overlays as $overlay) {
      $result[] = $overlay['markup'];
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
   * @return \Drupal\digital_signage_framework\Event\Overlays
   */
  public function addOverlay(string $id, $label, string $markup, array $attached): Overlays {
    $this->overlays[] = [
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
