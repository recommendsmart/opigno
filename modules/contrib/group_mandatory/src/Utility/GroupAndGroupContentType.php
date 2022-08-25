<?php

namespace Drupal\group_mandatory\Utility;

use Drupal\group\Entity\GroupContentTypeInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Combines a Group and a compatible GroupContentType.
 *
 * Although GroupType, EntityType, and Bundle usually determine a
 * GroupContentType, this is not enforced and assuming this may break anytime
 * in the future.
 * Apart from that robustness, this appears to immensely simplify code.
 */
final class GroupAndGroupContentType {

  protected GroupInterface $group;

  protected GroupContentTypeInterface $groupContentType;

  public function __construct(GroupInterface $group, GroupContentTypeInterface $groupContentType) {
    $this->group = $group;
    $this->groupContentType = $groupContentType;

    // According to the API, GCT's GroupType may be NULL, but that would break
    // this module in some other places, so care for that when it comes.
    $groupTypeId = $group->getGroupType()->id();
    $groupContentTypeGroupTypeId = $groupContentType->getContentPlugin()->getGroupTypeId();
    if ($groupContentTypeGroupTypeId !== $groupTypeId) {
      throw new \LogicException("GCT GroupType '$groupContentTypeGroupTypeId' does not match GroupType '$groupTypeId'.");
    }
  }

  /**
   * @return \Drupal\group\Entity\GroupInterface
   */
  public function getGroup(): GroupInterface {
    return $this->group;
  }

  /**
   * @return \Drupal\group\Entity\GroupContentTypeInterface
   */
  public function getGroupContentType(): GroupContentTypeInterface {
    return $this->groupContentType;
  }

}
