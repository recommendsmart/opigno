<?php

namespace Drupal\eca\Plugin\ECA\Modeller;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Plugin\EcaBase;
use Drupal\eca\Service\Actions;
use Drupal\eca\Service\Conditions;
use Drupal\eca\Service\Modellers;
use Drupal\eca_ui\Service\TokenBrowserService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for ECA modeller plugins.
 */
abstract class ModellerBase extends EcaBase implements ModellerInterface {

  use StringTranslationTrait;

  /**
   * ECA action service.
   *
   * @var \Drupal\eca\Service\Actions
   */
  protected Actions $actionServices;

  /**
   * ECA condition service.
   *
   * @var \Drupal\eca\Service\Conditions
   */
  protected Conditions $conditionServices;

  /**
   * ECA modeller service.
   *
   * @var \Drupal\eca\Service\Modellers
   */
  protected Modellers $modellerServices;

  /**
   * ECA token browser service.
   *
   * @var \Drupal\eca_ui\Service\TokenBrowserService
   */
  protected TokenBrowserService $tokenBrowserService;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * The documentation domain. May be NULL if not enabled or specified.
   *
   * @var string|null
   */
  protected ?string $documentationDomain;

  /**
   * ECA config entity.
   *
   * @var \Drupal\eca\Entity\Eca
   */
  protected Eca $eca;

  /**
   * Error flag.
   *
   * @var bool
   */
  protected bool $hasError = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): EcaBase {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->actionServices = $container->get('eca.service.action');
    $instance->conditionServices = $container->get('eca.service.condition');
    $instance->modellerServices = $container->get('eca.service.modeller');
    $instance->tokenBrowserService = $container->get('eca_ui.service.token_browser');
    $instance->logger = $container->get('logger.channel.eca');
    $instance->documentationDomain = $container->getParameter('eca.default_documentation_domain') ?
      $container->get('config.factory')->get('eca.settings')->get('documentation_domain') : NULL;
    return $instance;
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

  /**
   * {@inheritdoc}
   */
  public function hasError(): bool {
    return $this->hasError;
  }

}
