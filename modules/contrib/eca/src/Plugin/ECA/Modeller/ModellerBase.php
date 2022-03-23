<?php

namespace Drupal\eca\Plugin\ECA\Modeller;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eca\EcaState;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Plugin\EcaBase;
use Drupal\eca\Service\Actions;
use Drupal\eca\Service\Conditions;
use Drupal\eca\Service\Modellers;
use Drupal\eca\Service\TokenBrowserService;
use Drupal\eca\Token\TokenInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 *
 */
abstract class ModellerBase extends EcaBase implements ModellerInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\eca\Service\Actions
   */
  protected Actions $actionServices;

  /**
   * @var \Drupal\eca\Service\Conditions
   */
  protected Conditions $conditionServices;

  /**
   * @var \Drupal\eca\Service\Modellers
   */
  protected Modellers $modellerServices;

  /**
   * @var \Drupal\eca\Service\TokenBrowserService
   */
  protected TokenBrowserService $tokenBrowserService;

  /**
   * @var \Drupal\eca\Entity\Eca
   */
  protected Eca $eca;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Actions $action_services, Conditions $condition_services, Modellers $modeller_services, TokenBrowserService $token_browser_service, TokenInterface $token_services, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, RequestStack $request_stack, EcaState $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $token_services, $current_user, $entity_type_manager, $entity_type_bundle_info, $request_stack, $state);
    $this->actionServices = $action_services;
    $this->conditionServices = $condition_services;
    $this->modellerServices = $modeller_services;
    $this->tokenBrowserService = $token_browser_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): EcaBase {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eca.service.action'),
      $container->get('eca.service.condition'),
      $container->get('eca.service.modeller'),
      $container->get('eca.service.token_browser'),
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
  final public function setConfigEntity(Eca $eca): ModellerInterface {
    $this->eca = $eca;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEditable(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isExportable(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function edit(): array {
    return [];
  }

}
