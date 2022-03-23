<?php

namespace Drupal\eca_context\Event;

/**
 * Interface for events that may provide their own contexts.
 *
 * This is for events extending from\Drupal\Component\EventDispatcher\Event.
 */
interface ContextProviderInterface {

  /**
   * Get a list of contexts, keyed by purpose and unqualified context ID.
   *
   * When an event is configured to be triggered, ECA makes sure to load the
   * contexts returned by this method, before any logic defined by ECA config
   * is executed.
   *
   * A purpose identifies a certain context stack, which is in turn available
   * as a service. For example "account" identifies the context stack for
   * stacked contexts regards the currently logged in user account. That context
   * stack is available with service ID "context_stack.account".
   *
   * ECA additionally defines its own stack, namely "eca" and that one is also
   * available by its service ID "context_stack.eca".
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[][]
   *   The contexts, keyed by purpose and unqualified context ID.
   */
  public function getContexts(): array;

}
