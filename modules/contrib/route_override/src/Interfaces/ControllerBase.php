<?php

namespace Drupal\route_override\Interfaces;

use Drupal\route_override\Traits\AccessResultTrait;

abstract class ControllerBase implements ControllerInterface {

  use AccessResultTrait;

}
