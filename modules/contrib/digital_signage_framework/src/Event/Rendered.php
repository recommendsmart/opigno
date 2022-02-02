<?php

namespace Drupal\digital_signage_framework\Event;

use Drupal\digital_signage_framework\DeviceInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Rendered
 *
 * @package Drupal\digital_signage_framework\Event
 */
class Rendered extends Event {

  /**
   * @var \Symfony\Component\HttpFoundation\Response
   */
  protected $response;

  /**
   * @var \Drupal\digital_signage_framework\DeviceInterface
   */
  protected $device;

  /**
   * Rendered constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   * @param \Drupal\digital_signage_framework\DeviceInterface $device
   */
  public function __construct(Response $response, DeviceInterface $device) {
    $this->response = $response;
    $this->device = $device;
  }

  /**
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function getResponse(): Response {
    return $this->response;
  }

  /**
   * @return \Drupal\digital_signage_framework\DeviceInterface
   */
  public function getDevice(): DeviceInterface {
    return $this->device;
  }

}
