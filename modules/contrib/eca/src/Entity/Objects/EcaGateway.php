<?php

namespace Drupal\eca\Entity\Objects;

use Drupal\eca\Entity\Eca;

class EcaGateway extends EcaObject {

  /**
   * @var int
   */
  protected int $type;

  /**
   * Event constructor.
   *
   * @param \Drupal\eca\Entity\Eca $eca
   * @param string $id
   * @param string $label
   * @param \Drupal\eca\Entity\Objects\EcaEvent $event
   * @param int $type
   */
  public function __construct(Eca $eca, string $id, string $label, EcaEvent $event, int $type) {
    parent::__construct($eca, $id, $label, $event);
    $this->type = $type;
  }

}
