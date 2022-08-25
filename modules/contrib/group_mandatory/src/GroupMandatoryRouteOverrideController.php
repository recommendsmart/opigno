<?php
/** @noinspection PhpUnnecessaryLocalVariableInspection */

namespace Drupal\group_mandatory;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\CacheableTypes\CacheableBool;
use Drupal\group\Entity\GroupContentTypeInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\group_mandatory\Utility\GroupAndGroupContentType;
use Drupal\route_override\Interfaces\RouteOverrideControllerBase;
use Drupal\route_override\Traits\OverrideEntityFormByBundleTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

final class GroupMandatoryRouteOverrideController extends RouteOverrideControllerBase {

  use StringTranslationTrait;

  use OverrideEntityFormByBundleTrait;

  protected EntityTypeManagerInterface $entityTypeManager;

  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  protected GroupContentEnablerManagerInterface $groupContentPluginManager;

  protected GroupMembershipLoaderInterface $groupMembershipLoader;

  protected AccountProxyInterface $currentUser;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo, GroupContentEnablerManagerInterface $groupContentEnablerManager, GroupMembershipLoaderInterface $groupMembershipLoader, AccountProxyInterface $currentUser) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->groupContentPluginManager = $groupContentEnablerManager;
    $this->groupMembershipLoader = $groupMembershipLoader;
    $this->currentUser = $currentUser;
  }

  protected function appliesToRouteOfEntityFormOfBundle(ConfigEntityInterface $bundleConfig, EntityTypeInterface $entityType, Route $route): CacheableBool {
    $isCreateForm = $this->isEntityCreateFormRoute($route, $entityType->id());

    $bundleName = $bundleConfig->id();
    // Config has string IDs.
    assert(is_string($bundleName));
    $entityTypeId = $bundleConfig->getEntityType()->getBundleOf();
    $hasGroupMandatory = $this->checkEntityBundleHasGroupMandatory($entityTypeId, $bundleName);
    // Apply to creating entities of bundles with group-mandatory configured.
    $applies = CacheableBool::and($hasGroupMandatory, $isCreateForm);
    return $applies;
  }

  protected function getListCacheabilityOfAppliesToRoute(EntityTypeInterface $entityType): CacheableDependencyInterface {
    return $this->getListCacheabilityOfEntityTypeBeingGroupContentType();
  }

  public function appliesToRouteMatch(RouteMatchInterface $route_match, Request $request): CacheableBool {
    $entity = $this->extractEntityFromRouteMatchOfEntityForm($route_match);
    // Checking for create-/edit-/delete-form is not necessary here, as we only
    // subscribed to entity-create-form routes.
    $hasGroupMandatory = $this->checkEntityBundleHasGroupMandatory($entity->getEntityTypeId(), $entity->bundle());
    return $hasGroupMandatory;
  }

  protected function boolAccess(RouteMatchInterface $routeMatch, AccountInterface $account, Request $request = NULL): CacheableBool {
    // First we tried to check if we in-principle can create entities, even if
    // there is no group to do so. But the API was not friendly to that, any
    // access handler check needs a specific group and throws otherwise.
    // If and once we need it, we can copy the permission check from the access
    // handler.
    $entity = $this->extractEntityFromRouteMatchOfEntityForm($routeMatch);
    $groupAndGroupContentTypes = $this->fetchGroupAndGroupContentTypeItemsWithAccessToCreateGroupContent($entity->getEntityTypeId(), $entity->bundle(), $account);
    $cacheability = $this->fetchCacheabilityForGroupAndGroupContentTypeItemsWithAccess($entity->getEntityTypeId(), $entity->bundle(), $account);
    return CacheableBool::create((bool) $groupAndGroupContentTypes, $cacheability);
  }

  public function build(RouteMatchInterface $route_match, Request $request) {
    $entity = $this->extractEntityFromRouteMatchOfEntityForm($route_match);
    $bundleInfo = $this->entityTypeBundleInfo->getBundleInfo($entity->getEntityTypeId());
    $bundleLabel = $bundleInfo[$entity->bundle()]['label'];

    // No, this can not be injected with RequestValueResolvers.
    $account = $this->currentUser;
    $groupAndGroupContentTypes = $this->fetchGroupAndGroupContentTypeItemsWithAccessToCreateGroupContent($entity->getEntityTypeId(), $entity->bundle(), $account);
    $cacheability = $this->fetchCacheabilityForGroupAndGroupContentTypeItemsWithAccess($entity->getEntityTypeId(), $entity->bundle(), $account);

    $build = [];
    (CacheableMetadata::createFromRenderArray($build))
      ->addCacheableDependency($cacheability)
      ->applyTo($build);
    $links = array_map(
      function(GroupAndGroupContentType $groupAndGroupContentType) {
        $group = $groupAndGroupContentType->getGroup();
        $contentPluginId = $groupAndGroupContentType->getGroupContentType()->getContentPluginId();
        return [
          'group_label' => $group->label(),
          // @see \Drupal\group\Entity\Controller\GroupContentController::addPage
          'url' => Url::fromRoute('entity.group_content.create_form', [
            'group' => $group->id(),
            'plugin_id' => $contentPluginId,
          ])
        ];
      },
      $groupAndGroupContentTypes
    );

    if ($links) {
      $build = [
        [
          // If we add a title here, it will become page title.
          // Do not duplicate page title here.
          // '#title' => $this->t('Create %bundle', ['%bundle' => $bundleLabel]),
          '#theme' => 'group_mandatory_list',
          '#links' => $links,
        ]
      ];
    }
    else {
      $build = ['#markup' => $this->t('You must be member of a group to do this.')];
    }
    return $build;
  }


  protected function fetchGroupAndGroupContentTypeItemsWithAccessToCreateGroupContent(string $entityTypeId, string $bundleName, AccountInterface $account): array {
    // In the beginning, we tried iterating GroupContentTypes and check
    // GroupContent createAccess for that, as that is the information needed in
    // Group module itself to check access. We saw that the AccessControlHandler
    // needs the specific group in $context, even if Group itself does not use
    // that (but OK, there is a permissions-per-group (bah!) module that
    // leverages that.
    // So we must iterate all groups which may be expensive.
    // @todo Upstream: Ask a way to bulk check access.
    // @todo Upstream: Request a cheaper shortcut
    // @see \Drupal\group\Entity\Access\GroupContentAccessControlHandler::checkCreateAccess
    //
    // Then we saw that Group and EntityTypeWithBundle does not necessarily
    // determine GroupContentType, so invented GroupAndGroupContentType.
    //
    // @todo This is called twice, consider caching.

    $groupContentTypes = $this->fetchMandatoryGroupContentTypes($entityTypeId, $bundleName);
    $groups = $this->fetchGroupsForGroupContentTypes($groupContentTypes);
    $groupAndGroupContentTypeItems = $this->getGroupAndGroupContentTypeItemsWithAccess($groups, $groupContentTypes, $account);

    return $groupAndGroupContentTypeItems;
  }

  private function fetchGroupsForGroupContentTypes(array $groupContentTypes): array {
    /** @noinspection PhpUnhandledExceptionInspection */
    $groupStorage = $this->entityTypeManager->getStorage('group');

    $groupTypesByGroupContentTypeId = array_unique(array_map(
      fn(GroupContentTypeInterface $groupContentType) => $groupContentType->getGroupType(),
      $groupContentTypes
    ));
    $groupTypeIds = array_map(
      fn(GroupTypeInterface $groupType) => $groupType->id(),
      $groupTypesByGroupContentTypeId
    );
    // We asked GroupStorage.
    /** @var GroupInterface[] $groups */
    $groups = $groupStorage->loadByProperties(['type' => $groupTypeIds]);
    return $groups;
  }

  private function getGroupAndGroupContentTypeItemsWithAccess(array $groups, array $groupContentTypes, AccountInterface $account) {
    /** @noinspection PhpUnhandledExceptionInspection */
    $groupContentAccessControl = $this->entityTypeManager->getHandler('group_content', 'access');
    // We fetched an access handler.
    assert($groupContentAccessControl instanceof EntityAccessControlHandlerInterface);

    /** @var array<string, array<int, GroupContentTypeInterface>> $groupContentTypesByGroupTypeId */
    $groupContentTypesByGroupTypeId = array_reduce(
      $groupContentTypes,
      function (array $result, GroupContentTypeInterface $groupContentType) {
        $result[$groupContentType->getGroupTypeId()][] = $groupContentType;
        return $result;
      },
      []
    );
    $groupAndGroupContentTypeItems = [];
    foreach ($groups as $group) {
      $groupContentTypesForGroup = $groupContentTypesByGroupTypeId[$group->getGroupType()->id()];
      foreach ($groupContentTypesForGroup as $groupContentType) {
        // Using the access check for GroupContent give us not the desired
        // result, ich checks for "create relation", not "create entity".
        // @see \Drupal\group\Entity\Access\GroupContentAccessControlHandler::checkCreateAccess
        // Do the same access check as the route access checks for the
        // entity.group_content.create_form route do.
        // @see \Drupal\group\Entity\GroupContent
        // @see \Drupal\group\Entity\Routing\GroupContentRouteProvider::getCreateFormRoute
        // @see \Drupal\group\Access\GroupContentCreateEntityAccessCheck::access
        // > "Retrieve the access handler from the plugin manager instead."

        /** @noinspection PhpUnhandledExceptionInspection */
        $entityCreateAccess = $this->groupContentPluginManager
          ->getAccessControlHandler($groupContentType->getContentPluginId())
          ->entityCreateAccess($group, $account);
        if ($entityCreateAccess) {
          $groupAndGroupContentTypeItems[] = new GroupAndGroupContentType($group, $groupContentType);
        }
      }
    }
    return $groupAndGroupContentTypeItems;
  }

  protected function fetchCacheabilityForGroupAndGroupContentTypeItemsWithAccess(string $entityTypeId, string $bundleName, AccountInterface $account): CacheableDependencyInterface {
    $cacheability = new CacheableMetadata();
    // @todo Create and leverage CacheableArray.
    //   More precisely, if true, result is changed only after the last relevant
    //   GCT disappears (the any-item/list cacheability pattern).
    //   Leveraging the any-item/list cacheability pattern here would be too
    //   cumbersome, as we have no GCT array here, as we mapped GCT->GT in the
    //   other method.

    // Result is changed if any GCT is created or changed.
    $cacheability->addCacheableDependency($this->getListCacheabilityOfEntityTypeBeingGroupContentType());

    // Result is changed if users group permissions change.
    // Vary by user's group permissions.
    // @see https://www.drupal.org/docs/contributed-modules/group/turning-off-caching-when-it-doesnt-make-sense
    $cacheability->addCacheContexts(['user.group_permissions']);
    // Invalidated when group memberships of current user change?
    // No, we don't need this, as of the cache context above.
    // @see https://www.drupal.org/project/group/issues/3090833

    // Of course, results change if groups are added or changed.
    $cacheability->addCacheableDependency($this->getListCacheabilityOfEntityTypeBeingGroup());

    return $cacheability;
  }

  private function checkGroupContentTypeIsGroupMandatory(?GroupContentTypeInterface $maybeGroupContentType): CacheableBool {
    if ($maybeGroupContentType) {
      $isGroupMandatory = (bool) $maybeGroupContentType->getThirdPartySetting('group_mandatory', 'mandatory');
      if ($isGroupMandatory) {
        // Result is changed only when this GCT is changed.
        return CacheableBool::create(TRUE, $maybeGroupContentType);
      }
    }
    // Any newly created GCT may change this.
    return CacheableBool::create(FALSE, $this->getListCacheabilityOfEntityTypeBeingGroupContentType());
  }

  protected function checkEntityBundleHasGroupMandatory(string $entityTypeId, string $bundleName): CacheableBool {
    $mandatoryGroupContentTypes = $this->fetchMandatoryGroupContentTypes($entityTypeId, $bundleName);
    if ($mandatoryGroupContentTypes) {
      // Result will change only after the last config is changed.
      $arbitraryGroupContentType = reset($mandatoryGroupContentTypes);
      return CacheableBool::create(TRUE, $arbitraryGroupContentType);
    }
    else {
      // Any newly created GCT may change this.
      return CacheableBool::create(FALSE, $this->getListCacheabilityOfEntityTypeBeingGroupContentType());
    }
  }

  private function getListCacheabilityOfEntityTypeBeingGroupContentType(): CacheableDependencyInterface {
    return $this->getListCacheabilityOfEntityType('group_content_type');
  }

  private function getListCacheabilityOfEntityTypeBeingGroup(): CacheableDependencyInterface {
    return $this->getListCacheabilityOfEntityType('group');
  }

  private function getListCacheabilityOfEntityType(string $entityId): CacheableDependencyInterface {
    /** @noinspection PhpUnhandledExceptionInspection */
    $groupContentTypeEntityType = $this->entityTypeManager->getDefinition($entityId);
    assert($groupContentTypeEntityType instanceof EntityTypeInterface);
    $cacheability = (new CacheableMetadata())
      ->setCacheTags($groupContentTypeEntityType->getListCacheTags())
      ->setCacheContexts($groupContentTypeEntityType->getListCacheContexts());
    return $cacheability;
  }

  /**
   * @return array<string, GroupContentTypeInterface>
   */
  private function fetchGroupContentTypesForEntityBundle(string $entityTypeId, string $bundleName): array {
    /** @noinspection PhpUnhandledExceptionInspection */
    $groupContentTypeStorage = $this->entityTypeManager->getStorage('group_content_type');
    $groupContentTypes = $groupContentTypeStorage->loadMultiple();
    $applicableGroupContentTypes = array_filter($groupContentTypes,
      fn(GroupContentTypeInterface $groupContentType) =>
        $groupContentType->getContentPlugin()->getEntityTypeId() === $entityTypeId
        && $groupContentType->getContentPlugin()->getEntityBundle() === $bundleName
    );
    return $applicableGroupContentTypes;
  }

  /**
   * @return array<string, GroupContentTypeInterface>
   */
  private function fetchMandatoryGroupContentTypes(string $entityTypeId, string $bundleName): array {
    $applicableGroupContentTypes = $this->fetchGroupContentTypesForEntityBundle($entityTypeId, $bundleName);
    $mandatoryGroupContentTypes = array_filter($applicableGroupContentTypes,
      fn(GroupContentTypeInterface $groupContentType) => $this->checkGroupContentTypeIsGroupMandatory($groupContentType)
    );
    return $mandatoryGroupContentTypes;
  }

}
