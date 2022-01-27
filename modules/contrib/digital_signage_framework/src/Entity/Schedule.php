<?php

namespace Drupal\digital_signage_framework\Entity;

use Drupal;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\digital_signage_framework\ScheduleInterface;

/**
 * Defines the digital signage schedule entity class.
 *
 * @ContentEntityType(
 *   id = "digital_signage_schedule",
 *   label = @Translation("Digital signage schedule"),
 *   label_collection = @Translation("Digital signage schedules"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\digital_signage_framework\ScheduleListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {},
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "digital_signage_schedule",
 *   admin_permission = "administer digital signage schedule",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/digital_signage_schedule/{digital_signage_schedule}",
 *     "collection" = "/admin/content/digital-signage-schedule"
 *   },
 * )
 */
class Schedule extends ContentEntityBase implements ScheduleInterface {

  protected $needsPush = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems(): array {
    $items = $this->get('items')->getValue();
    if (empty($items)) {
      return [];
    }
    return isset($items[0]['type']) ? $items : $items[0];
  }

  /**
   * {@inheritdoc}
   */
  public function needsPush($flag = NULL): bool {
    if ($flag !== NULL) {
      $this->needsPush = $flag;
    }
    return $this->needsPush;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the schedule was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setLabel(t('Hash'))
      ->setDescription(t('An md5 hash over the input arguments that were used to create this schedule in order to determine if we need to create a new one or can use the existing one.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 32);

    $fields['items'] = BaseFieldDefinition::create('map')
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setLabel(t('Items'))
      ->setDescription(t('An array of SequenceItems.'))
      ->setRequired(TRUE);

    // TODO: Is there a way to inject this service as a dependency?
    /** @var \Drupal\digital_signage_framework\PlatformPluginManager $platformManager */
    $platformManager = Drupal::service('plugin.manager.digital_signage_platform');
    foreach ($platformManager->getAllPlugins() as $plugin) {
      $plugin->scheduleBaseFields($fields);
    }

    return $fields;
  }

}
