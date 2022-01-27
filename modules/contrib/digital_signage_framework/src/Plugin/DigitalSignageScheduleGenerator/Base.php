<?php

namespace Drupal\digital_signage_framework\Plugin\DigitalSignageScheduleGenerator;

use Drupal\digital_signage_framework\Entity\ContentSetting;
use Drupal\digital_signage_framework\DefaultDuration;
use Drupal\digital_signage_framework\DefaultWeight;
use Drupal\digital_signage_framework\ScheduleGeneratorPluginBase;
use Drupal\digital_signage_framework\SequenceItem;
use Drupal\digital_signage_framework\WeightInterface;

/**
 * Plugin implementation of the digital_signage_schedule_generator.
 *
 * @DigitalSignageScheduleGenerator(
 *   id = "default",
 *   label = @Translation("Default Schedule Generator"),
 *   description = @Translation("Default Schedule Generator provided by the core digital signage framework.")
 * )
 */
class Base extends ScheduleGeneratorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function generate($device, $contentSettings): array {
    $sequenceItems = [];
    $criticalSequenceItems = [];
    /** @var \Drupal\digital_signage_framework\Entity\ContentSetting[] $nonCriticalEntities */
    $nonCriticalEntities = [];
    $nonChosenEntities = [];

    //TODO use dependency injection
    $duration = new DefaultDuration($this->settings);
    $weight = new DefaultWeight($this->settings);
    $weightSum = 0;

    foreach ($contentSettings as $contentSetting) {
      if ($contentSetting->isEnabled()) {
        if ($contentSetting->isCritical()) {
          $criticalSequenceItems[] = SequenceItem::create($contentSetting, $duration->getDurationByComplexity($contentSetting->getType()));
        }
        else {
          $nonCriticalEntities[] = $contentSetting;
          $nonChosenEntities[] = $contentSetting;
          $weightSum += $weight->getWeightByPriority($contentSetting->getPriority());
        }
      }
    }

    $weightMap = $this->getWeightMap($nonCriticalEntities, $weight);

    /** @var \Drupal\digital_signage_framework\Entity\ContentSetting $contentEntityTmp */
    $contentEntityTmp = NULL;
    for ($i = 0; $i < $weightSum; $i++) {
      $contentEntity = $this->getNonCriticalEntity($weightMap, $weightSum);
      if($contentEntityTmp !== NULL && $contentEntityTmp->getReverseEntityId() === $contentEntity->getReverseEntityId()) {
        continue;
      }
      /** @noinspection SlowArrayOperationsInLoopInspection */
      $sequenceItems = array_merge($sequenceItems, $criticalSequenceItems);
      $contentEntityTmp = $contentEntity;
      $sequenceItems[] = SequenceItem::create($contentEntity, $duration->getDurationByComplexity($contentEntity->getType()));

      //Remove from non chosen entity array.
      $key = array_search($contentEntity, $nonChosenEntities, TRUE);
      unset($nonChosenEntities[$key]);
    }

    usort($nonChosenEntities, array($this,'sortItemsByPriority'));
    foreach ($nonChosenEntities as $nonChosenEntity) {
      /** @noinspection SlowArrayOperationsInLoopInspection */
      $sequenceItems = array_merge($sequenceItems, $criticalSequenceItems);
      $sequenceItems[] = SequenceItem::create($nonChosenEntity, $duration->getDurationByComplexity($nonChosenEntity->getType()));
    }
    return $sequenceItems;
  }

  /**
   * Sort by priority function for entities.
   *
   * @param \Drupal\digital_signage_framework\Entity\ContentSetting $contentSetting
   * @param \Drupal\digital_signage_framework\Entity\ContentSetting $otherContentSetting
   *
   * @return int
   */
  private function sortItemsByPriority(ContentSetting $contentSetting, ContentSetting $otherContentSetting): int {
    if($contentSetting->getPriority() === $otherContentSetting->getPriority()) {
      return 0;
    }
    return ($contentSetting->getPriority() < $otherContentSetting->getPriority()) ? -1 : 1;
  }

  /**
   * Returns a sequence item based on the given weight.
   *
   * @param $weightMap
   * @param $weightSum
   *
   * @return \Drupal\digital_signage_framework\Entity\ContentSetting
   */
  private function getNonCriticalEntity($weightMap, $weightSum): ContentSetting {
    /** @noinspection RandomApiMigrationInspection */
    $randomNumber = rand(1, $weightSum);
    foreach ($weightMap as $key => $value) {
      if ($randomNumber <= $key) {
        return $value;
      }
    }

    return end($weightMap);
  }

  /**
   * Returns a weighted array for entities.
   *
   * @param \Drupal\digital_signage_framework\Entity\ContentSetting[] $nonCriticalEntities
   * @param \Drupal\digital_signage_framework\WeightInterface $weight
   *
   * @return array
   */
  private function getWeightMap(array $nonCriticalEntities, WeightInterface $weight): array {
    $weightMap = [];
    $calculatedSum = 0;
    foreach ($nonCriticalEntities as $contentEntity) {
      $relativeWeight = $weight->getWeightByPriority($contentEntity->getPriority());
      $calculatedSum += $relativeWeight;
      $weightMap[$calculatedSum] = $contentEntity;
    }

    return $weightMap;
  }

}
