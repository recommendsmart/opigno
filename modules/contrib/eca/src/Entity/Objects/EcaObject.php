<?php

namespace Drupal\eca\Entity\Objects;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Entity\EcaObjectTrait;

abstract class EcaObject {

  use EcaObjectTrait;

  /**
   * @var \Drupal\eca\Entity\Eca
   */
  protected Eca $eca;

  /**
   * @var \Drupal\eca\Entity\Objects\EcaEvent
   */
  protected EcaEvent $event;

  /**
   * The most recent set predecessor.
   *
   * @var \Drupal\eca\Entity\Objects\EcaObject|null
   */
  protected ?EcaObject $predecessor;

  /**
   * @var array
   */
  protected array $configuration = [];

  /**
   * @var array
   */
  protected array $successors = [];

  /**
   * @var string
   */
  protected string $id;

  /**
   * @var string
   */
  protected string $label;

  /**
   * Constructor.
   *
   * @param \Drupal\eca\Entity\Eca $eca
   * @param string $id
   * @param string $label
   * @param \Drupal\eca\Entity\Objects\EcaEvent $event
   */
  public function __construct(Eca $eca, string $id, string $label, EcaEvent $event) {
    $this->eca = $eca;
    $this->id = $id;
    $this->label = $label;
    $this->event = $event;
  }

  /**
   * @return \Drupal\eca\Entity\Objects\EcaEvent
   */
  public function getEvent(): EcaEvent {
    return $this->event;
  }

  /**
   * Get the configuration of this object.
   *
   * @return array
   *   The configuration.
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * @param string $key
   * @param mixed $value
   *
   * @return $this
   */
  public function setConfiguration(string $key, $value): EcaObject {
    $this->configuration[$key] = $value;
    return $this;
  }

  /**
   * @param array $successors
   *
   * @return $this
   */
  public function setSuccessors(array $successors): EcaObject {
    $this->successors = $successors;
    return $this;
  }

  /**
   * @return array
   */
  public function getSuccessors(): array {
    return $this->successors;
  }

  /**
   * @return string
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * @return string
   */
  public function getLabel(): string {
    return $this->label ?? 'noname';
  }

  /**
   * @param \Drupal\eca\Entity\Objects\EcaObject $predecessor
   * @param \Drupal\Component\EventDispatcher\Event $event
   * @param array $context
   *
   * @return bool
   */
  public function execute(EcaObject $predecessor, Event $event, array $context): bool {
    $this->predecessor = $predecessor;
    return TRUE;
  }

}
