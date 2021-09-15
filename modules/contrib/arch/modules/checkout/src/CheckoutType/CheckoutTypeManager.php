<?php

namespace Drupal\arch_checkout\CheckoutType;

use Drupal\arch_checkout\CheckoutType\Exception\CheckoutTypeException;
use Drupal\arch_checkout\Form\CheckoutSettingsForm;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\FilteredPluginManagerTrait;

/**
 * Manages discovery and instantiation of checkout type plugins.
 *
 * @see \Drupal\arch_checkout\CheckoutType\CheckoutTypePluginInterface
 */
class CheckoutTypeManager extends DefaultPluginManager implements CheckoutTypeManagerInterface, FallbackPluginManagerInterface {

  use CategorizingPluginManagerTrait {
    getSortedDefinitions as traitGetSortedDefinitions;
  }
  use FilteredPluginManagerTrait;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a CheckoutTypeManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct(
      'Plugin/CheckoutType',
      $namespaces,
      $module_handler,
      'Drupal\arch_checkout\CheckoutType\CheckoutTypePluginInterface',
      'Drupal\arch_checkout\CheckoutType\Annotation\CheckoutType'
    );

    $this->alterInfo($this->getType());
    $this->setCacheBackend($cache_backend, 'checkout_type_plugins');
    $this->logger = $logger_factory->get('CheckoutTypeManager');
    $this->configFactory = $config_factory;
    $this->config = $config_factory->get(CheckoutSettingsForm::CONFIG_NAME);
  }

  /**
   * {@inheritdoc}
   */
  protected function getType() {
    return 'checkout_type';
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultCheckoutType($throwable = TRUE) {
    $definitions = $this->getDefinitions();

    // Sort the plugins first by category, then by admin label.
    $definitions = $this->traitGetSortedDefinitions($definitions, 'admin_label');

    // Do not display the 'broken' plugin in the UI.
    unset($definitions['broken']);

    if (
      count($definitions) < 1
      && $throwable
    ) {
      throw new CheckoutTypeException('There is no checkout type definition found in the system. Please provide one to continue.');
    }

    $default_type = $this->config->get('plugin_id');
    if (!empty($default_type)) {
      if (!isset($definitions[$default_type])) {
        $this->logger->error('The plugin marked as default is not found: @plugin_id', ['plugin_id' => $default_type]);
      }
      else {
        return $definitions[$default_type];
      }
    }

    // If there is no saved default plugin we gives the first one back.
    return current($definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    $this->processDefinitionCategory($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedDefinitions(array $definitions = NULL) {
    // Sort the plugins first by category, then by admin label.
    $definitions = $this->traitGetSortedDefinitions($definitions, 'admin_label');
    // Do not display the 'broken' plugin in the UI.
    unset($definitions['broken']);
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'broken';
  }

  /**
   * {@inheritdoc}
   */
  protected function handlePluginNotFound($plugin_id, array $configuration) {
    $this->logger->warning('The "%plugin_id" was not found', ['%plugin_id' => $plugin_id]);
    return parent::handlePluginNotFound($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function isAnonymousCheckoutAllowed() {
    return $this->config->get('anonymous_checkout') === 'allow';
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRedirectIfCartEmpty() {
    return $this->config->get('redirect_to_cart_if_empty') === 'redirect_to_cart';
  }

}
