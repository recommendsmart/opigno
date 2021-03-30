<?php

namespace Drupal\group_behavior;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupContentType;
use Drupal\group\Entity\GroupContentTypeInterface;
use Drupal\group\Entity\GroupTypeInterface;

class GroupBehaviorHelpers {

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return array
   */
  public static function fetchGroupBehaviorGroupsOfEntity(EntityInterface $entity) {
    $groups = [];
    if ($entity instanceof ContentEntityInterface) {
      if ($groupContents = GroupContent::loadByEntity($entity)) {
        /** @var \Drupal\group\Entity\GroupContentInterface[] $groupContents */
        foreach ($groupContents as $groupContent) {
          $groupContentType = $groupContent->getGroupContentType();
          if (static::checkGroupContentTypeHasGroupBehavior($groupContentType)) {
            $groups[] = $groupContent->getGroup();
          }
        }
      }
    }
    return $groups;
  }

  /**
   * Filter group content by type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\group\Entity\GroupContentTypeInterface $groupContentType
   *
   * @return \Drupal\group\Entity\GroupContentInterface[]
   */
  public static function fetchEntityGroupContentOfType(EntityInterface $entity, GroupContentTypeInterface $groupContentType) {
    if ($entity instanceof ContentEntityInterface) {
      $entityGroupContents = GroupContent::loadByEntity($entity);
      $groupContentTypeId = $groupContentType->id();
      return array_filter($entityGroupContents, function (GroupContentInterface $groupContent) use ($groupContentTypeId) {
        return $groupContent->getGroupContentType()->id() === $groupContentTypeId;
      });
    }
    else {
      return [];
    }
  }

  /**
   * Get applicable group content types.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return \Drupal\group\Entity\GroupContentTypeInterface[]
   *   Group content types.
   */
  public static function fetchGroupContentTypesWithGroupBehaviorByEntity(EntityInterface $entity) {
    /** @var \Drupal\group\Entity\GroupContentTypeInterface[] $groupContentTypes */
    $groupContentTypes = GroupContentType::loadByEntityTypeId($entity->getEntityTypeId());
    $groupContentTypes = self::filterGroupContentTypesWithGroupBehavior($groupContentTypes);
    $groupContentTypes = self::filterGroupContentTypesByBundle($groupContentTypes, $entity->bundle());
    return $groupContentTypes;
  }

  /**
   * Filter group content types by setting.
   *
   * @param \Drupal\group\Entity\GroupContentTypeInterface[] $groupContentTypes
   *   Group content types.
   *
   * @return \Drupal\group\Entity\GroupContentTypeInterface[]
   *   Group content types.
   */
  protected static function filterGroupContentTypesWithGroupBehavior($groupContentTypes) {
    $applicableGroupContentTypes = [];
    foreach ($groupContentTypes as $id => $groupContentType) {
      if (self::checkGroupContentTypeHasGroupBehavior($groupContentType)) {
        $applicableGroupContentTypes[$id] = $groupContentType;
      }
    }
    return $applicableGroupContentTypes;
  }

  /**
   * @param \Drupal\group\Entity\GroupContentTypeInterface[] $groupContentTypes
   *   Group content types.
   * @param string $bundle
   *   The bundle.
   * @return \Drupal\group\Entity\GroupContentTypeInterface[]
   *   Group content types.
   */
  protected static function filterGroupContentTypesByBundle($groupContentTypes, $bundle) {
    $applicableGroupContentTypes = [];
    foreach ($groupContentTypes as $id => $groupContentType) {
      if ($bundle === $groupContentType->getContentPlugin()->getEntityBundle()) {
        $applicableGroupContentTypes[$id] = $groupContentType;
      }
    }
    return $applicableGroupContentTypes;
  }

  /**
   * @param \Drupal\group\Entity\GroupContentTypeInterface $groupContentType
   *
   * @return bool
   */
  public static function checkGroupContentTypeHasGroupBehavior(GroupContentTypeInterface $groupContentType) {
    return $groupContentType->getContentPluginId() ===
      self::getGroupBehaviorContentPluginId($groupContentType->getGroupType());
  }

  /**
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *
   * @return mixed
   */
  public static function getGroupBehaviorContentPluginId(GroupTypeInterface $groupType) {
    return self::getGroupTypeSetting($groupType, 'content_plugin');
  }

  /**
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerInterface|null
   */
  public static function getGroupBehaviorContentPlugin(GroupTypeInterface $groupType) {
    /** @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface $pluginManager */
    $pluginManager = \Drupal::service('plugin.manager.group_content_enabler');
    $contentPluginId = GroupBehaviorHelpers::getGroupBehaviorContentPluginId($groupType);
    try {
      /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $contentPlugin */
      $contentPlugin = $pluginManager->createInstance($contentPluginId);
    }
    catch (PluginException $e) {
      $contentPlugin = NULL;
    }
    return $contentPlugin;
  }

  /**
   * @param \Drupal\group\Entity\GroupTypeInterface $groupType
   * @param $id
   *
   * @return mixed
   */
  public static function getGroupTypeSetting(GroupTypeInterface $groupType, $id) {
    return $groupType
      ->getThirdPartySetting('group_behavior', $id);
  }

}
