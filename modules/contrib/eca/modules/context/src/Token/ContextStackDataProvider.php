<?php

namespace Drupal\eca_context\Token;

use Drupal\context_stack\ContextStackTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\eca\Token\DataProviderInterface;

/**
 * Context stacks as data provider for the Token environment.
 */
class ContextStackDataProvider implements DataProviderInterface {

  use ContextStackTrait;

  /**
   * The ContextStackDataProvider constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->addContextStack($this->getContextStack('eca'));
    if ($module_handler->moduleExists('context_stack_account')) {
      $this->addContextStack($this->getContextStack('account'));
    }
    if ($module_handler->moduleExists('context_stack_view')) {
      $this->addContextStack($this->getContextStack('view'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getData(string $key) {
    foreach ($this->contextStacks as $context_stack) {
      if ($context_stack->hasContext($key)) {
        $context = $context_stack->getContext($key);
        if ($context->hasContextValue()) {
          return $context->getContextData();
        }
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasData(string $key): bool {
    foreach ($this->contextStacks as $context_stack) {
      if ($context_stack->hasContext($key) && $context_stack->getContext($key)->hasContextValue()) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
