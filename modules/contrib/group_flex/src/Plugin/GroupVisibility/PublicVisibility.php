<?php

namespace Drupal\group_flex\Plugin\GroupVisibility;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group_flex\Plugin\GroupVisibilityBase;

/**
 * Provides a 'public' group visibility.
 *
 * @GroupVisibility(
 *  id = "public",
 *  label = @Translation("Public (visible by anybody authorised to view a Group of that type)"),
 *  weight = -100
 * )
 */
class PublicVisibility extends GroupVisibilityBase {

  /**
   * {@inheritdoc}
   */
  public function enableGroupType(GroupTypeInterface $groupType): void {
    $mappedPerm = [
      $groupType->getOutsiderRoleId() => [
        'view group' => TRUE,
      ],
      $groupType->getMemberRoleId() => [
        'view group' => TRUE,
      ],
    ];
    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function disableGroupType(GroupTypeInterface $groupType): void {
    $mappedPerm = [
      $groupType->getOutsiderRoleId() => [
        'view group' => FALSE,
      ],
      $groupType->getMemberRoleId() => [
        'view group' => TRUE,
      ],
    ];
    $this->saveMappedPerm($mappedPerm, $groupType);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermissions(GroupInterface $group): array {
    return [
      $group->getGroupType()->getOutsiderRoleId() => ['view group'],
      $group->getGroupType()->getMemberRoleId() => ['view group'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupLabel(GroupTypeInterface $groupType): string {
    return t('Public (The @group_type_name will be viewed by non-members of the group)', ['@group_type_name' => $groupType->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getValueDescription(GroupTypeInterface $groupType): string {
    return t('The @group_type_name will be viewed by non-members of the group', ['@group_type_name' => $groupType->label()]);
  }

}
