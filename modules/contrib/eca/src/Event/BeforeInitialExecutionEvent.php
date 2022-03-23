<?php

namespace Drupal\eca\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Entity\Objects\EcaEvent;

/**
 * Dispatches before initial successor execution of an ECA configuration.
 */
class BeforeInitialExecutionEvent extends Event {

  /**
   * The ECA configuration.
   *
   * @var \Drupal\eca\Entity\Eca
   */
  protected Eca $eca;

  /**
   * The ECA event object.
   *
   * @var \Drupal\eca\Entity\Objects\EcaEvent
   */
  protected EcaEvent $ecaEvent;

  /**
   * The applying system event.
   *
   * @var \Drupal\Component\EventDispatcher\Event
   */
  protected Event $event;

  /**
   * Array holding arbitrary variables the represent a pre-execution state.
   *
   * Can be used to hold and restore values after execution.
   *
   * @var array
   */
  protected $prestate = [];

  /**
   * The BeforeInitialExecutionEvent constructor.
   *
   * @param \Drupal\eca\Entity\Eca $eca
   *   The ECA configuration.
   * @param \Drupal\eca\Entity\Objects\EcaEvent $ecaEvent
   *   The ECA event object.
   * @param \Drupal\Component\EventDispatcher\Event $event
   *   The applying system event.
   */
  public function __construct(Eca $eca, EcaEvent $ecaEvent, Event $event) {
    $this->eca = $eca;
    $this->ecaEvent = $ecaEvent;
    $this->event = $event;
  }

  /**
   * Get the ECA configuration.
   *
   * @return \Drupal\eca\Entity\Eca
   *   The ECA configuration.
   */
  public function getEca(): Eca {
    return $this->eca;
  }

  /**
   * Get the ECA event object.
   *
   * @return \Drupal\eca\Entity\Objects\EcaEvent
   *   The ECA event object.
   */
  public function getEcaEvent(): EcaEvent {
    return $this->ecaEvent;
  }

  /**
   * Get the applying system event.
   *
   * @return \Drupal\Component\EventDispatcher\Event
   *   The applying system event.
   */
  public function getEvent(): Event {
    return $this->event;
  }

  /**
   * Get the value of a prestate variable.
   *
   * @param string|null $name
   *   The name of the variable. Set to NULL to return the whole array.
   *
   * @return mixed
   *   The value. Returns NULL if not present.
   */
  public function &getPrestate(?string $name) {
    if (!isset($name)) {
      return $this->prestate;
    }

    $value = NULL;
    if (isset($this->prestate[$name])) {
      $value = &$this->prestate[$name];
    }
    return $value;
  }

  /**
   * Set the value of a prestate variable.
   *
   * @param string $name
   *   The name of the variable.
   * @param mixed &$value
   *   The value to set, passed by reference.
   */
  public function setPrestate(string $name, &$value) {
    $this->prestate[$name] = &$value;
  }

}
