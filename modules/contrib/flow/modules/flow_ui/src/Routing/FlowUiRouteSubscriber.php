<?php

namespace Drupal\flow_ui\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\flow\FlowTaskMode;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Flow UI routes.
 */
class FlowUiRouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (!$route_name = $entity_type->get('field_ui_base_route')) {
        continue;
      }
      if (!$entity_route = $collection->get($route_name)) {
        continue;
      }
      $path = $entity_route->getPath();

      $options = $entity_route->getOptions();
      if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
        $options['parameters'][$bundle_entity_type] = [
          'type' => 'entity:' . $bundle_entity_type,
        ];
      }
      // Special parameter used to easily recognize all Flow UI routes.
      $options['_flow_ui'] = TRUE;

      $defaults = [
        '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::flowForm',
        'entity_type_id' => $entity_type_id,
      ];
      // If the entity type has no bundles and it doesn't use {bundle} in its
      // admin path, use the entity type.
      if (strpos($path, '{bundle}') === FALSE) {
        $defaults['bundle'] = !$entity_type->hasKey('bundle') ? $entity_type_id : '';
      }

      $requirements = ['_permission' => 'administer flow+administer ' . $entity_type_id . ' flow'];

      $route = new Route(
        "$path/flow",
        [
          '_title' => 'Manage flow',
          'flow_task_mode' => FlowTaskMode::service()->getDefaultTaskMode(),
        ] + $defaults,
        $requirements,
        $options
      );
      $collection->add("entity.flow.{$entity_type_id}.default", $route);
      $route = new Route(
        "$path/flow/{flow_task_mode}",
        [
          '_title' => 'Manage flow',
        ] + $defaults,
        $requirements,
        $options
      );
      $collection->add("entity.flow.{$entity_type_id}.task_mode", $route);

      $route = new Route(
        "$path/flow/{flow_task_mode}/add/{flow_task_plugin}/{flow_subject_plugin}",
        [
          '_title' => 'Add task',
          '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::taskAddForm',
        ] + $defaults,
        $requirements,
        $options
      );
      $collection->add("flow.task.{$entity_type_id}.add", $route);
      $route = new Route(
        "$path/flow/{flow_task_mode}/edit/{flow_task_index}",
        [
          '_title' => 'Edit task',
          '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::taskEditForm',
        ] + $defaults,
        $requirements,
        $options
      );
      $collection->add("flow.task.{$entity_type_id}.edit", $route);
      $route = new Route(
        "$path/flow/{flow_task_mode}/enable/{flow_task_index}",
        [
          '_title' => 'Enable task',
          '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::taskEnableForm',
        ] + $defaults,
        $requirements,
        $options
      );
      $collection->add("flow.task.{$entity_type_id}.enable", $route);
      $route = new Route(
        "$path/flow/{flow_task_mode}/disable/{flow_task_index}",
        [
          '_title' => 'Disable task',
          '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::taskDisableForm',
        ] + $defaults,
        $requirements,
        $options
      );
      $collection->add("flow.task.{$entity_type_id}.disable", $route);
      $route = new Route(
        "$path/flow/{flow_task_mode}/delete/{flow_task_index}",
        [
          '_title' => 'Delete task',
          '_controller' => '\Drupal\flow_ui\Controller\FlowUiController::taskDeleteForm',
        ] + $defaults,
        $requirements,
        $options
      );
      $collection->add("flow.task.{$entity_type_id}.delete", $route);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -75];
    return $events;
  }

}
