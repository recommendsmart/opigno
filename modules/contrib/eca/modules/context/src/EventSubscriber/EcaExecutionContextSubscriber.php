<?php

namespace Drupal\eca_context\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\context_stack\ContextStackFactoryTrait;
use Drupal\context_stack\ContextStackTrait;
use Drupal\context_stack\Plugin\Context\GenericEntityContext;
use Drupal\eca\EcaEvents;
use Drupal\eca\Event\AfterActionExecutionEvent;
use Drupal\eca\Event\AfterInitialExecutionEvent;
use Drupal\eca\Event\BeforeActionExecutionEvent;
use Drupal\eca\Event\BeforeInitialExecutionEvent;
use Drupal\eca\Event\ContentEntityEventInterface;
use Drupal\eca\EventSubscriber\EcaBase;
use Drupal\eca_context\Event\ContextProviderInterface;

/**
 * Makes stacked context values available for ECA execution logic.
 */
class EcaExecutionContextSubscriber extends EcaBase {

  use ContextStackTrait;
  use ContextStackFactoryTrait;

  /**
   * Subscriber method before initial execution.
   *
   * @param \Drupal\eca\Event\BeforeInitialExecutionEvent $before_event
   *   The according event.
   */
  public function onBeforeInitialExecution(BeforeInitialExecutionEvent $before_event): void {
    $event = $before_event->getEvent();
    // For each execution call, we create new layer(s) of context(s).
    $context_collections = [];
    // Push all contexts provided by the event to their according stacks.
    if ($event instanceof ContextProviderInterface) {
      foreach ($event->getContexts() as $purpose => $stack_contexts) {
        if (!($context_stack = $this->getContextStack($purpose))) {
          continue;
        }
        $collection = $this->getContextStackFactory()->createCollection();
        foreach ($stack_contexts as $id => $stack_context) {
          $collection->addContext($stack_context, $id);
        }
        $context_collections[$purpose][] = $collection;
        $context_stack->push($collection);
      }
    }
    // Take care of the ECA context stack, which will be mostly used
    // within any configured logic.
    if ($context_stack = $this->getContextStack('eca')) {
      if ($event instanceof ContentEntityEventInterface && ($entity = $event->getEntity())) {
        // If given, push the entity that belongs to the given event as a
        // new collection layer to the context stack of ECA.
        $id = 'entity';
        if (!$context_stack->hasContext($id) || ($context_stack->getContext($id)->getContextValue() !== $entity)) {
          $context_collections['eca'][] = $context_stack->addContext(GenericEntityContext::fromEntity($entity), $id);
        }
      }
      if (empty($context_collections['eca'])) {
        // We provide at least one layer of a context collection, so that
        // after successor execution, leftovers are guaranteed to be popped
        // off the stack.
        $collection = $this->getContextStackFactory()->createCollection();
        $context_collections['eca'][] = $collection;
        $context_stack->push($collection);
      }
    }
    $before_event->setPrestate('context_collections', $context_collections);
  }

  /**
   * Subscriber method after initial execution.
   *
   * @param \Drupal\eca\Event\AfterInitialExecutionEvent $after_event
   *   The according event.
   */
  public function onAfterInitialExecution(AfterInitialExecutionEvent $after_event): void {
    // After successor execution, we pop off previously added context
    // collections, so that subsequent events are not interferred by
    // runtime changes made by the configured logic for this event.
    $context_collections = $after_event->getPrestate('context_collections') ?? [];
    foreach ($context_collections as $purpose => $collections) {
      if ($context_stack = $this->getContextStack($purpose)) {
        while ($collection = array_pop($collections)) {
          // We pop items until we get to our collection instance. That way
          // we make sure, that other events will not get interferred by
          // collection leftovers, possibly coming from negligent plugins.
          /** @noinspection LoopWhichDoesNotLoopInspection */
          /** @noinspection PhpStatementHasEmptyBodyInspection */
          /** @noinspection MissingOrEmptyGroupStatementInspection */
          while ($collection !== $context_stack->pop() && $context_stack->current()) {}
        }
      }
    }
  }

  /**
   * Subscriber method before action execution.
   *
   * @param \Drupal\eca\Event\BeforeActionExecutionEvent $before_event
   *   The according event.
   */
  public function onBeforeActionExecution(BeforeActionExecutionEvent $before_event): void {
    $object = $before_event->getObject();
    $context_collection = NULL;
    $stack_context = NULL;
    if ($object instanceof EntityInterface) {
      $stack_context = GenericEntityContext::fromEntity($object);
    }
    elseif ($object instanceof TypedDataInterface) {
      $value = $object->getValue();
      if ($value instanceof EntityInterface) {
        $object = $value;
        $stack_context = GenericEntityContext::fromEntity($object);
      }
      else {
        $stack_context = new Context(ContextDefinition::create($object->getDataDefinition()->getDataType()), $object);
      }
    }
    if ($stack_context && $context_stack = $this->getContextStack('eca')) {
      $type_parts = explode(':', $stack_context->getContextData()->getDataDefinition()->getDataType());
      $context_id = end($type_parts);
      $same_context_already_exists = FALSE;
      if ($existing_context = $context_stack->getContext($context_id)) {
        $same_context_already_exists = $existing_context->hasContextValue() && $existing_context->getContextValue() === $stack_context->getContextValue();
      }
      if ($same_context_already_exists) {
        $stack_context = NULL;
      }
      else {
        $context_collection = $context_stack->addContext($stack_context, $context_id);
      }
    }
    $before_event->setPrestate('context_collection', $context_collection);
  }

  /**
   * Subscriber method after action execution.
   *
   * @param \Drupal\eca\Event\AfterActionExecutionEvent $after_event
   *   The according event.
   */
  public function onAfterActionExecution(AfterActionExecutionEvent $after_event): void {
    // As the inner logic of a plugin may have added its own context
    // collection(s), they need to be popped off too, and after our
    // context collection got removed, we restore the children in the same
    // order they were added.
    if (($context_collection = $after_event->getPrestate('context_collection')) && $context_stack = $this->getContextStack('eca')) {
      $child_collections = [];
      while ($popped_collection = $context_stack->pop()) {
        if ($popped_collection === $context_collection) {
          foreach ($child_collections as $child_collection) {
            $context_stack->push($child_collection);
          }
          break;
        }
        array_unshift($child_collections, $popped_collection);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[EcaEvents::BEFORE_INITIAL_EXECUTION][] = ['onBeforeInitialExecution'];
    $events[EcaEvents::AFTER_INITIAL_EXECUTION][] = ['onAfterInitialExecution'];
    $events[EcaEvents::BEFORE_ACTION_EXECUTION][] = ['onBeforeActionExecution'];
    $events[EcaEvents::AFTER_ACTION_EXECUTION][] = ['onAfterActionExecution'];
    return $events;
  }

}
