<?php

namespace Drupal\eca_context\Plugin\Action;

use Drupal\context_stack\ContextCollectionInterface;
use Drupal\context_stack\ContextStackTrait;
use Drupal\context_stack\Plugin\Context\GenericEntityContext;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca\Plugin\CleanupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a new context.
 *
 * @Action(
 *   id = "eca_new_context",
 *   label = @Translation("Define a new context")
 * )
 */
class NewContext extends ConfigurableActionBase implements CleanupInterface {

  use ContextStackTrait;

  /**
   * The instantiated context collection, if any.
   *
   * @var \Drupal\context_stack\ContextCollectionInterface|null
   */
  protected ?ContextCollectionInterface $contextCollection = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    /** @var \Drupal\eca_queue\Plugin\Action\EnqueueTask $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->addContextStack($container->get('context_stack.eca'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if (isset($this->contextCollection)) {
      return;
    }

    $context_id = trim($this->tokenServices->replaceClear($this->configuration['context_id']));
    if ($context_id === '') {
      $context_id = NULL;
    }
    $from_token = trim($this->tokenServices->replace($this->configuration['from_token']));
    $data = $this->tokenServices->getTokenData($from_token);
    if (isset($data)) {
      if ($data instanceof EntityInterface) {
        $context = GenericEntityContext::fromEntity($data);
      }
      elseif ($data instanceof TypedDataInterface) {
        $context = new Context(ContextDefinition::create($data->getDataDefinition()->getDataType()), $data);
      }
      else {
        $context = NULL;
      }
      if ($context && $context->hasContextValue()) {
        $context_stack = $this->getContextStack('eca');
        $this->contextCollection = $context_stack->addContext($context, $context_id);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'context_id' => '',
      'from_token' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function cleanupAfterSuccessors(): void {
    if (isset($this->contextCollection)) {
      $context_stack = $this->getContextStack('eca');
      $context_stack->pop();
      $this->contextCollection = NULL;
    }
  }

}
