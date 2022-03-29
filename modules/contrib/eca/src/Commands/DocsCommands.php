<?php

namespace Drupal\eca\Commands;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Action\ActionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\eca\Entity\Eca;
use Drupal\eca\Plugin\ECA\Condition\ConditionInterface;
use Drupal\eca\Plugin\ECA\Event\EventInterface;
use Drupal\eca\Service\Actions;
use Drupal\eca\Service\Conditions;
use Drupal\eca\Service\Modellers;
use Drush\Commands\DrushCommands;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\ArrayLoader as TwigLoader;

/**
 * A Drush commandfile.
 */
class DocsCommands extends DrushCommands {

  public const DOMAIN = 'eca.docs.lakedrops.com';

  protected array $toc = [];
  protected array $modules = [];

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
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * @var \Twig\Loader\ArrayLoader
   */
  protected TwigLoader $twigLoader;

  /**
   * @var \Twig\Environment
   */
  protected TwigEnvironment $twigEnvironment;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * DocsCommands constructor.
   *
   */
  public function __construct(Actions $actionServices, Conditions $conditionServices, Modellers $modellerServices, FileSystemInterface $fileSystem, ModuleHandlerInterface $moduleHandler) {
    parent::__construct();
    $this->actionServices = $actionServices;
    $this->conditionServices = $conditionServices;
    $this->modellerServices = $modellerServices;
    $this->fileSystem = $fileSystem;
    $this->twigLoader = new TwigLoader();
    $this->twigEnvironment = new TwigEnvironment($this->twigLoader);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Export documentation for all plugins.
   *
   * @usage eca:doc:plugins
   *   Export documentation for all plugins.
   *
   * @command eca:doc:plugins
   */
  public function plugins(): void {
    foreach ($this->modellerServices->events() as $event) {
      $this->pluginDoc($event);
    }
    foreach ($this->conditionServices->conditions() as $condition) {
      $this->pluginDoc($condition);
    }
    foreach ($this->actionServices->actions() as $action) {
      $this->pluginDoc($action);
    }
    foreach ($this->modules as $id => $values) {
      $values['toc'][$values['provider_name']]['Home'] = $values['provider_path'] . '/index.md';
      file_put_contents('../mkdocs/docs/' . $values['provider_path'] . '/index.md', $this->render(__DIR__ . '/provider.md.twig', $values));
    }
    $this->updateToc();
  }

  /**
   * Export documentation for all models.
   *
   * @usage eca:doc:models
   *   Export documentation for all models.
   *
   * @command eca:doc:models
   */
  public function models(): void {
    foreach (\Drupal::entityTypeManager()
               ->getStorage('eca')
               ->loadMultiple() as $eca) {
      $this->modelDoc($eca);
    }
    $this->updateToc();
  }

  private function updateToc(): void {
    $toc = explode(PHP_EOL, Yaml::encode($this->toc));
    foreach ($toc as $id => $line) {
      $line = '    - ' . $line;
      while (mb_strpos($line, '-   ')) {
        $line = str_replace('-   ', '  - ', $line);
      }
      $toc[$id] = $line;
    }
    file_put_contents('../mkdocs/docs/toc.yml', implode(PHP_EOL, $toc));
  }

  private function pluginDoc(PluginInspectionInterface $plugin): void {
    $values = $this->getPluginValues($plugin);
    $this->modules[$values['provider']] = $values;
    $id = str_replace([':'], '_', $plugin->getPluginId());
    $path = $values['path'];
    $filename = $path . '/' . $id . '.md';
    @$this->fileSystem->mkdir('../mkdocs/docs/' . $path, NULL, TRUE);
    file_put_contents('../mkdocs/docs/' . $filename, $this->render(__DIR__ . '/plugin.md.twig', $values));
    $values['toc'][$values['provider_name']][ucfirst($values['type']) . 's'][(string) $values['label']] = $filename;
  }

  private function getPluginValues(PluginInspectionInterface $plugin): array {
    $values = $plugin->getPluginDefinition();
    if ($values['provider'] === 'core') {
      $values['provider_name'] = 'Drupal core';
    }
    else {
      $values['provider_name'] = $this->moduleHandler->getName($values['provider']);
    }
    if (mb_strpos($values['provider'], 'eca_') === 0) {
      $basePath = str_replace('_', '/', $values['provider']);
      $values['toc'] = &$this->toc['ECA'];
    }
    else {
      $basePath = $values['provider'];
      $values['toc'] = &$this->toc;
    }
    if ($plugin instanceof EventInterface) {
      $type = 'event';
      $fields = $plugin->fields();
    }
    elseif ($plugin instanceof ConditionInterface) {
      $type = 'condition';
      $fields = $this->conditionServices->fields($plugin);
    }
    elseif ($plugin instanceof ActionInterface) {
      $type = 'action';
      $fields = $this->actionServices->fields($plugin);
    }
    else {
      $type = 'error';
      $fields = [];
    }
    $values['path'] = sprintf('plugins/%s/%ss',
      $basePath,
      $type
    );
    $values['provider_path'] = sprintf('plugins/%s',
      $basePath,
    );
    $values['type'] = $type;
    $values['fields'] = $fields;
    return $values;
  }

  private function modelDoc(Eca $eca): void {
    try {
      $model = $eca->getModel();
    }
    catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
      return;
    }
    $tags = $model->getTags();
    $values = [
      'id' => str_replace([':', ' '], '_', mb_strtolower($eca->label())),
      'label' => $eca->label(),
      'version' => $eca->get('version'),
      'main_tag' => $tags[0],
      'tags' => $tags,
      'documentation' => $model->getDocumentation(),
      'dependencies' => $eca->getDependencies(),
      'events' => $eca->getEventInfos(),
      'model_filename' => $eca->getModeller()->getPluginId() . '-' . $eca->id(),
      'library_path' => 'library/' . $tags[0],
    ];

    @$this->fileSystem->mkdir('../mkdocs/docs/' . $values['library_path'] . '/' . $values['id'], NULL, TRUE);

    file_put_contents('../mkdocs/docs/' . $values['library_path'] . '/' . $values['id'] . '.md', $this->render(__DIR__ . '/library.md.twig', $values));
    file_put_contents('../mkdocs/docs/' . $values['library_path'] . '/' . $values['id'] . '/' . $values['model_filename'] . '.xml', $model->getModeldata());

    $this->toc[$values['main_tag']][$values['label']] = $values['library_path'] . '/' . $values['id'] . '.md';
  }

  private function render(string $filename, array $values): string {
    $this->twigLoader->setTemplate($filename, file_get_contents($filename));
    try {
      return $this->twigEnvironment->render($filename, $values);
    } catch (LoaderError|RuntimeError|SyntaxError $e) {
    }
    return '';
  }

}
