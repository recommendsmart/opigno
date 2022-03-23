<?php

namespace Drupal\eca\Entity\Objects;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Plugin\ECA\Event\EventInterface;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Plugin\ObjectWithPluginInterface;
use Drupal\eca_content\Event\ContentEntityBaseEntity;

class EcaEvent extends EcaObject implements ObjectWithPluginInterface {

  /**
   * @var \Drupal\eca\Plugin\ECA\Event\EventInterface
   */
  protected EventInterface $plugin;

  /**
   * @var array
   */
  protected array $variables = [];

  /**
   * Event constructor.
   *
   * @param \Drupal\eca\Entity\Eca $eca
   * @param string $id
   * @param string $label
   * @param \Drupal\eca\Plugin\ECA\Event\EventInterface $plugin
   */
  public function __construct(Eca $eca, string $id, string $label, EventInterface $plugin) {
    parent::__construct($eca, $id, $label, $this);
    $this->plugin = $plugin;
  }

  /**
   * @param \Drupal\Component\EventDispatcher\Event $event
   *
   * @return bool
   */
  public function applies(Event $event): bool {
    if (get_class($event) === $this->plugin->drupalEventClass() && (!($event instanceof ConditionalApplianceInterface) || $event->applies($this->getId(), $this->configuration))) {
      if ($event instanceof ContentEntityBaseEntity) {
        return !empty($event->getEntity());
      }
      else {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get the plugin instance.
   *
   * @return \Drupal\eca\Plugin\ECA\Event\EventInterface
   *   The plugin instance.
   */
  public function getPlugin(): EventInterface {
    return $this->plugin;
  }

}
