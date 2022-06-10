<?php

namespace Drupal\eca\Service;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Action\ActionInterface;
use Drupal\Core\Action\ActionManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\eca_content\Plugin\Action\FieldUpdateActionBase;

/**
 * Service class for Drupal core actions in ECA.
 */
class Actions {

  use ServiceTrait;

  /**
   * Action plugin manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected ActionManager $actionManager;

  /**
   * Logger channel service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Actions constructor.
   *
   * @param \Drupal\Core\Action\ActionManager $action_manager
   *   The action plugin manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type managewr service.
   */
  public function __construct(ActionManager $action_manager, LoggerChannelInterface $logger, EntityTypeManagerInterface $entity_type_manager) {
    $this->actionManager = $action_manager;
    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Returns a sorted list of action plugins.
   *
   * @return \Drupal\Core\Action\ActionInterface[]
   *   The sorted list of actions.
   */
  public function actions(): array {
    $actions = &drupal_static('eca_actions');
    if ($actions === NULL) {
      $actions = [];
      foreach ($this->actionManager->getDefinitions() as $plugin_id => $definition) {
        if (!empty($definition['confirm_form_route_name'])) {
          // We cannot support actions that redirect to a confirmation form.
          // @see https://www.drupal.org/project/eca/issues/3279483
          continue;
        }
        if ($definition['id'] === 'entity:save_action') {
          // We replace all save actions by one generic "Entity: save" action.
          continue;
        }
        try {
          $actions[] = $this->actionManager->createInstance($plugin_id);
        }
        catch (PluginException $e) {
          // Can be ignored.
        }
      }
      $this->sortPlugins($actions);
    }
    return $actions;
  }

  /**
   * Prepares all the fields of an action plugin for modellers.
   *
   * @param \Drupal\Core\Action\ActionInterface $action
   *   The action plugin for which the fields need to be prepared.
   *
   * @return array
   *   The list of fields for this action.
   */
  public function fields(ActionInterface $action): array {
    $fields = [];
    if (($action instanceof ConfigurableInterface || $action instanceof FieldUpdateActionBase) && $config = $action->defaultConfiguration()) {
      $this->prepareConfigFields($fields, $config, $action);
    }

    try {
      $actionType = $action->getPluginDefinition()['type'] ?? '';
      if ($actionType === 'entity' || $this->entityTypeManager->getDefinition($actionType, FALSE)) {
        $fields[] = [
          'name' => 'object',
          'label' => 'Entity',
          'type' => 'String',
          'value' => '',
        ];
      }
    }
    catch (PluginNotFoundException $e) {
      // Can be ignore as we set $exception_on_invalid to FALSE.
    }

    return $fields;
  }

}
