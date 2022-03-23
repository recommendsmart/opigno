<?php

namespace Drupal\eca\Plugin\ECA\Condition;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eca\EcaState;
use Drupal\eca\Service\Conditions;
use Drupal\eca\Token\TokenInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Base class for ECA provided conditions.
 */
abstract class ConditionBase extends PluginBase implements ConditionInterface, ContainerFactoryPluginInterface {

  use ContextAwarePluginTrait;

  /**
   * @var \Drupal\Component\EventDispatcher\Event
   */
  protected Event $event;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity type and bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The ECA-related token services.
   *
   * @var \Drupal\eca\Token\TokenInterface
   */
  protected TokenInterface $tokenServices;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * @var \Drupal\eca\EcaState
   */
  protected EcaState $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, RequestStack $request_stack, TokenInterface $token_services, AccountProxyInterface $current_user, TimeInterface $time, EcaState $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->requestStack = $request_stack;
    $this->tokenServices = $token_services;
    $this->currentUser = $current_user;
    $this->time = $time;
    $this->state = $state;
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ConditionBase {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('request_stack'),
      $container->get('eca.token_services'),
      $container->get('current_user'),
      $container->get('datetime.time'),
      $container->get('eca.state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function reset(): ConditionInterface {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEvent(Event $event): ConditionInterface {
    $this->event = $event;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEvent(): Event {
    return $this->event;
  }

  /**
   * {@inheritdoc}
   */
  public function isNegated(): bool {
    $negated = $this->configuration['negate'] ?? FALSE;
    if ($negated === Conditions::OPTION_YES) {
      $negated = TRUE;
    }
    elseif ($negated === Conditions::OPTION_NO) {
      $negated = FALSE;
    }
    return $negated;
  }

  /**
   * Reverse the boolean result, if plugin configuration has negation turned on.
   *
   * @param bool $result
   *   Boolean result before optional negation.
   *
   * @return bool
   *   The real result after negation settings has been checked and applied.
   */
  protected function negationCheck(bool $result): bool {
    return $this->isNegated() ? !$result : $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['negate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Negate the condition'),
      '#default_value' => $this->configuration['negate'],
      '#weight' => 1,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $negated = $form_state->getValue('negate', FALSE);
    $this->configuration['negate'] = $negated && ($negated !== Conditions::OPTION_NO);
    if ($form_state->hasValue('context_mapping')) {
      $this->setContextMapping($form_state->getValue('context_mapping'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return [
        'id' => $this->getPluginId(),
      ] + $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): ConditionBase {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'negate' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    return [];
  }

}
