<?php

namespace Drupal\eca\Entity;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\eca\Entity\Objects\EcaObject;
use Drupal\eca\Plugin\Action\ActionInterface;
use Drupal\Core\Action\ActionInterface as CoreActionInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\eca\Event\ContentEntityEventInterface;
use Drupal\eca\Event\FormEventInterface;
use Drupal\eca\Plugin\ECA\Condition\ConditionInterface;
use Drupal\eca\Plugin\ObjectWithPluginInterface;
use Drupal\eca\Token\TokenInterface;

/**
 * A trait for ECA config entities and ECA objects.
 *
 * This trait provides a number of services that we can't inject as dependencies
 * and also provides a set of functions that help to receive a data object from
 * the ECA token service depending on the current context.
 */
trait EcaObjectTrait {

  /**
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannel $logger;

  /**
   * @var \Drupal\eca\Token\TokenInterface
   */
  protected TokenInterface $token;

  /**
   * A static list of key fields that possibly hold a token.
   *
   * @var array
   */
  protected static $keyFields = ['entity', 'object'];

  /**
   * Returns the ECA logger channel as a service.
   *
   * @return \Drupal\Core\Logger\LoggerChannel
   */
  protected function logger(): LoggerChannel {
    if (!isset($this->logger)) {
      $this->logger = \Drupal::service('logger.channel.eca');
    }
    return $this->logger;
  }

  /**
   * Returns the ECA token service.
   *
   * @return \Drupal\eca\Token\TokenInterface
   */
  protected function token(): TokenInterface {
    if (!isset($this->token)) {
      $this->token = \Drupal::service('eca.token_services');
    }
    return $this->token;
  }

  /**
   * Returns the applicable data objects for the given plugin.
   *
   * The plugin is either an action or a condition plugin and depending on their
   * type property, this method determines which is the correct data object
   * upon which the action should execute or condition should assert.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $plugin
   *   The action or condition plugin for which the data object is required.
   *
   * @return \Drupal\Component\EventDispatcher\Event[]|\Drupal\Core\Entity\EntityInterface[]
   *   The appropriate data objects for the given plugin in the current context.
   *   The returned array may contain NULL values.
   */
  public function getObjects(PluginInspectionInterface $plugin): array {
    $actionType = $plugin->getPluginDefinition()['type'] ?? '';
    switch ($actionType) {
      case NULL:
      case '':
        // The plugin doesn't provide any type declaration, it doesn't require
        // any data object for that matter then.
        return [NULL];

      case 'form':
        // The plugin executes upon a form event and this will determine
        // the correct form event to be returned.
        return [$this->getFormEvent($plugin)];

      case 'system':
      case 'entity':
        // The plugin executes upon an entity and this will determine the
        // correct entity (or multiple entities) for the current context.
        return $this->getEntities($plugin) + [NULL];

    }
    // The plugin declares another type, i.e. none of the above. If the
    // given type is an entity type ID and the context provides an entity
    // of that given entity type, this is then the required one and will
    // be returned.
    $entities = [];
    foreach ($this->getEntities($plugin) as $entity) {
      if ($entity->getEntityTypeId() === $actionType) {
        $entities[] = $entity;
      }
    }
    return $entities + [NULL];
  }

  /**
   * Determine the correct entities for the $plugin in the current context.
   *
   * If the plugin is configurable and an entity is being declared as the
   * required one by a set key field, this will grab that object from the token
   * service using the defined key and returns it.
   *
   * If the plugin does not request a specific object, the following lookups
   * will be performed (only for actions and conditions):
   * - Check if the plugin ID contains a hint to the used entity / token type.
   * - Ask predecessor(s) for having a previously declared object. If the
   *   nearest predecessor has one.
   * - Ask the triggering event for an entity and return it.
   * - As a last resort, lookup the context stack of ECA regards an entity.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $plugin
   *   The action or condition plugin for which the data object is required.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The required entities if available.
   *   May return an empty array if no entity was found.
   */
  protected function getEntities(PluginInspectionInterface $plugin): array {
    $token_service = $this->token();
    if ($plugin instanceof ConfigurableInterface) {
      $config = $plugin->getConfiguration();
    }
    elseif (isset($plugin->configuration)) {
      $config = $plugin->configuration;
    }
    elseif (isset($this->configuration)) {
      $config = $this->configuration;
    }

    // If the plugin is configurable and an entity is being declared as the
    // required one by a set key field, this will grab that object from the
    // token service using the defined key and returns it.
    if (!empty($config)) {
      foreach (static::$keyFields as $key_field) {
        if (isset($config[$key_field]) && is_string($config[$key_field]) && trim($config[$key_field]) !== '') {
          if ($data = $this->filterEntities($token_service->getTokenData($config[$key_field]))) {
            return $data;
          }
        }
      }
    }

    if ($plugin instanceof ActionInterface || $plugin instanceof CoreActionInterface || $plugin instanceof ConditionInterface) {
      // Check if the plugin ID contains a hint to the used entity / token type.
      $id_parts = explode(':', $plugin->getPluginId());
      while ($id_part = array_pop($id_parts)) {
        if ($data = $this->filterEntities($token_service->getTokenData($id_part))) {
          return $data;
        }
        if ($type = $token_service->getTokenTypeForEntityType($id_part)) {
          if ($type !== $id_part && ($data = $this->filterEntities($token_service->getTokenData($type)))) {
            return $data;
          }
        }
      }

      // Ask predecessor(s) for having previously declared entities.
      $predecessor = $this->predecessor ?? NULL;
      if ($predecessor instanceof ObjectWithPluginInterface && $predecessor instanceof EcaObject) {
        if ($objects = $this->filterEntities($predecessor->getObjects($predecessor->getPlugin()))) {
          return $objects;
        }
      }

      if (method_exists($plugin, 'getEvent')) {
        // Ask the triggering event for an entity and return it.
        $event = $plugin->getEvent();
        if ($event instanceof ContentEntityEventInterface && ($entity = $event->getEntity())) {
          return [$entity];
        }
      }

      // If available, lookup the ECA context stack for an available entity.
      if (\Drupal::hasService('context_stack.eca')) {
        /** @var \Drupal\context_stack\ContextStackInterface $context_stack */
        $context_stack = \Drupal::service('context_stack.eca');
        if ($context_stack->hasContext('entity')) {
          if ($context = $context_stack->getContext('entity')) {
            if ($context->hasContextValue()) {
              $value = $context->getContextValue();
              if ($value instanceof EntityInterface) {
                return [$value];
              }
            }
          }
        }
      }
    }
    return [];
  }

  /**
   * Determine the correct form event for the $plugin in the current context.
   *
   * If the plugin is being executed in the context of a form event, that
   * event will be returned such that the plugin can later retrieve the form
   * and formState objects from that event for further processing.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $plugin
   *   The action or condition plugin for which the data object is required.
   *
   * @return \Drupal\Component\EventDispatcher\Event|null
   *   The required form event if available or NULL otherwise.
   */
  protected function getFormEvent(PluginInspectionInterface $plugin): ?Event {
    if ($plugin instanceof ActionInterface || $plugin instanceof ConditionInterface) {
      $event = $plugin->getEvent();
      if ($event instanceof FormEventInterface) {
        return $event;
      }
    }
    return NULL;
  }

  /**
   * Helper method that returns an array that only contains entities.
   *
   * @param mixed $data
   *   The data to filter.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The array containing only entities (may be empty).
   */
  protected function filterEntities($data) {
    if ($data instanceof EntityInterface) {
      return [$data];
    }
    if ($data instanceof EntityReferenceFieldItemListInterface) {
      return array_values($data->referencedEntities());
    }
    if ($data instanceof EntityReferenceItem) {
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $parent */
      $parent = $data->getParent();
      $entities = $parent->referencedEntities();
      foreach ($parent as $delta => $item) {
        if ($item === $data) {
          return [$entities[$delta]];
        }
      }
    }
    if ($data instanceof EntityAdapter) {
      $data = [$data];
    }

    $entities = [];
    if (is_iterable($data)) {
      foreach ($data as $value) {
        if ($value instanceof TypedDataInterface) {
          $value = $value->getValue();
        }
        if ($value instanceof EntityInterface && !in_array($value, $entities, TRUE)) {
          $entities[] = $value;
        }
      }
    }
    return $entities;
  }

}
