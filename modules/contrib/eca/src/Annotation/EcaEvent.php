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

  public string $drupal_id;

  public string $drupal_event_class;

  public int $tags;

}
