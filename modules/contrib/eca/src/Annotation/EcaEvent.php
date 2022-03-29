<?php

namespace Drupal\eca\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines ECA event annotation object.
 *
 * @Annotation
 */
class EcaEvent extends Plugin {

  public string $label;

  public string $event_name;

  public string $event_class;

  public int $tags;

}
