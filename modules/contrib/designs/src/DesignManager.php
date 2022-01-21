<?php

namespace Drupal\designs;

use Drupal\Component\Annotation\Plugin\Discovery\AnnotationBridgeDecorator;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscoveryDecorator;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\designs\Annotation\Design;
use Drupal\designs\Discovery\YamlDirectoryDiscoveryDecorator;

/**
 * Provides a plugin manager for designs.
 */
class DesignManager extends DefaultPluginManager implements DesignManagerInterface {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected ThemeHandlerInterface $themeHandler;

  /**
   * The source manager.
   *
   * @var \Drupal\designs\DesignSourceManagerInterface
   */
  protected DesignSourceManagerInterface $sourceManager;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * DesignPluginManager constructor.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler to invoke the alter hook with.
   * @param \Drupal\designs\DesignSourceManagerInterface $sourceManager
   *   The design source manager.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, DesignSourceManagerInterface $sourceManager, FileSystemInterface $fileSystem) {
    parent::__construct('Plugin/designs/design', $namespaces, $module_handler, DesignInterface::class, Design::class);
    $this->themeHandler = $theme_handler;
    $this->sourceManager = $sourceManager;
    $this->fileSystem = $fileSystem;

    $type = $this->getType();
    $this->setCacheBackend($cache_backend, $type);
    $this->alterInfo($type);
  }

  /**
   * {@inheritdoc}
   */
  protected function getType() {
    return 'design';
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    return $this->moduleHandler->moduleExists($provider) || $this->themeHandler->themeExists($provider);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $discovery = new AnnotatedClassDiscovery($this->subdir, $this->namespaces, $this->pluginDefinitionAnnotationName, $this->additionalAnnotationNamespaces);

      $directories = $this->moduleHandler->getModuleDirectories() + $this->themeHandler->getThemeDirectories();
      $discovery = new YamlDiscoveryDecorator($discovery, 'designs', $directories);
      $discovery
        ->addTranslatableProperty('label')
        ->addTranslatableProperty('description')
        ->addTranslatableProperty('category');

      // Scan for designs within designs directory.
      $designs = array_map(function ($directory) {
        if (file_exists("{$directory}/designs")) {
          $designs = $this->fileSystem->scanDirectory("{$directory}/designs", "/^.*\.yml$/");
          $designs = array_map(function ($file) {
            return dirname($file->uri);
          }, $designs);
          return array_unique(array_values($designs));
        }
        return [];
      }, $directories);

      $discovery = new YamlDirectoryDiscoveryDecorator($discovery, 'designs', $designs, $directories);
      $discovery
        ->addTranslatableProperty('label')
        ->addTranslatableProperty('description')
        ->addTranslatableProperty('category');
      $discovery = new AnnotationBridgeDecorator($discovery, $this->pluginDefinitionAnnotationName);
      $discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
      $this->discovery = $discovery;
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    if (!$definition instanceof DesignDefinition) {
      throw new InvalidPluginDefinitionException($plugin_id, sprintf('The "%s" design definition must extend %s', $plugin_id, DesignDefinition::class));
    }

    // Add the module or theme path to the 'path'.
    $provider = $definition->getProvider();
    if ($this->moduleHandler->moduleExists($provider)) {
      $base_path = $this->moduleHandler->getModule($provider)->getPath();
    }
    elseif ($this->themeHandler->themeExists($provider)) {
      $base_path = $this->themeHandler->getTheme($provider)->getPath();
    }
    else {
      $base_path = '';
    }

    $path = $definition->getPath();
    $path = !empty($path) ? $base_path . '/' . $path : $base_path;
    $definition->setPath($path);

    // Add dependencies from libraries.
    foreach ($definition->getLibraries() as $library) {
      if (!is_string($library)) {
        continue;
      }
      $config_dependencies = $definition->getConfigDependencies();
      [$library_provider] = explode('/', $library, 2);
      if ($this->moduleHandler->moduleExists($library_provider)) {
        $config_dependencies['module'][] = $library_provider;
      }
      elseif ($this->themeHandler->themeExists($library_provider)) {
        $config_dependencies['theme'][] = $library_provider;
      }
      $definition->setConfigDependencies($config_dependencies);
    }

    if (!$definition->getDefaultRegion()) {
      $definition->setDefaultRegion(key($definition->getRegions()));
    }

    // Make sure settings are translatable.
    $settings = $this->getTranslatedDefinitions(
      $definition->getSettings(), ['label', 'description']
    );
    $definition->setSettings($settings);

    // Makes sure region names are translatable.
    $regions = $this->getTranslatedDefinitions(
      $definition->getRegions(), ['label', 'description']
    );
    $definition->setRegions($regions);
  }

  /**
   * Get the labels as translatable markup.
   *
   * @param array[] $array
   *   The array of arrays with a key of 'label'.
   * @param array $keys
   *   The keys.
   *
   * @return array
   *   The array having been translated.
   */
  protected function getTranslatedDefinitions(array $array, array $keys) {
    return array_map(function ($item) use ($keys) {
      foreach ($keys as $key) {
        if (!isset($item[$key])) {
          continue;
        }
        if (!$item[$key] instanceof TranslatableMarkup) {
          $item[$key] = new TranslatableMarkup($item[$key], [], ['context' => 'design_region']);
        }
      }
      return $item;
    }, $array);
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraryImplementations() {
    $libraries = [];
    /** @var \Drupal\designs\DesignDefinition[] $definitions */
    $definitions = $this->getDefinitions();
    foreach ($definitions as $definition) {
      $libraries += $definition->getLibraryInfo();
    }
    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    // Fetch all categories from definitions and remove duplicates.
    $categories = array_unique(array_values(array_map(function (DesignDefinition $definition) {
      return $definition->getCategory();
    }, $this->getDefinitions())));
    natcasesort($categories);
    return array_values($categories);
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\designs\DesignDefinition[]
   *   The design definitions.
   */
  public function getSortedDefinitions(array $definitions = NULL, $label_key = 'label') {
    // Sort the plugins first by category, then by label.
    $definitions = $definitions ?? $this->getDefinitions();
    uasort($definitions, function (DesignDefinition $a, DesignDefinition $b) {
      if ($a->getCategory() != $b->getCategory()) {
        return strnatcasecmp($a->getCategory(), $b->getCategory());
      }
      return strnatcasecmp($a->getLabel(), $b->getLabel());
    });
    return $definitions;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\designs\DesignDefinition[][]
   *   The grouped design definitions.
   */
  public function getGroupedDefinitions(array $definitions = NULL, $label_key = 'label') {
    $definitions = $this->getSortedDefinitions($definitions ?? $this->getDefinitions(), $label_key);
    $grouped_definitions = [];
    foreach ($definitions as $id => $definition) {
      $grouped_definitions[(string) $definition->getCategory()][$id] = $definition;
    }
    return $grouped_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getDesignOptions() {
    $design_options = [];
    foreach ($this->getGroupedDefinitions() as $category => $design_definitions) {
      foreach ($design_definitions as $name => $design_definition) {
        $design_options[$category][$name] = $design_definition->getLabel();
      }
    }
    return $design_options;
  }

  /**
   * {@inheritdoc}
   */
  public function createSourcedInstance($design_id, array $design_configuration, $source_id, array $source_configuration) {
    try {
      $design = $this->createInstance($design_id, $design_configuration);
      $source = $this->sourceManager->createInstance($source_id, $source_configuration);
      $design->setSourcePlugin($source);
    }
    catch (PluginNotFoundException $e) {
      return NULL;
    }
    return $design;
  }

}
