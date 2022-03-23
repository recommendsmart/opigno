<?php

namespace Drupal\eca\Plugin;

use Drupal\Component\Plugin\PluginBase as DrupalPluginBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eca\EcaState;
use Drupal\eca\Token\TokenInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Base class for ECA provided event, condition and action plugins.
 */
abstract class EcaBase extends DrupalPluginBase implements EcaInterface, ContainerFactoryPluginInterface {

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
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * @var \Drupal\eca\EcaState
   */
  protected EcaState $state;

  /**
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\eca\Token\TokenInterface $token_services
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   * @param \Drupal\eca\EcaState $state
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TokenInterface $token_services, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, RequestStack $requestStack, EcaState $state) {
    $this->tokenServices = $token_services;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->requestStack = $requestStack;
    $this->state = $state;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): EcaBase {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eca.token_services'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('request_stack'),
      $container->get('eca.state')
    );
  }

  /**
   * {@inheritdoc}
   */
  final public function drupalId(): string {
    return $this->pluginDefinition['drupal_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [];
  }

}
