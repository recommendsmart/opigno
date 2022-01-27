<?php

namespace Drupal\digital_signage_framework;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class Query {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\digital_signage_framework\Emergency
   */
  protected $emergency;

  /**
   * Renderer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\digital_signage_framework\Emergency $emergency
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Emergency $emergency) {
    $this->entityTypeManager = $entity_type_manager;
    $this->emergency = $emergency;
  }

  /**
   * @param \Drupal\digital_signage_framework\DeviceInterface[] $devices
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   */
  public function allEntitiesForSelect(array $devices): array {
    $entities = [];
    foreach ($devices as $device) {
      if ($schedule = $device->getSchedule(FALSE)) {
        foreach ($schedule->getItems() as $item) {
          $key = implode('/', [$item['entity']['type'], $item['entity']['id']]);
          if (!isset($entities[$key])) {
            try {
              if ($entity = $this->entityTypeManager->getStorage($item['entity']['type'])
                ->load($item['entity']['id'])) {
                $entities[$key] = $entity->label();
              }
            }
            catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
              // Can be ignored
            }
          }
        }
      }
    }
    foreach ($this->emergency->allForSelect() as $key => $label) {
      $entities[$key] = $label;
    }
    return $entities;
  }

}
