<?php

namespace Drupal\eca\Generators;

use DrupalCodeGenerator\Command\ModuleGenerator;

/**
 * Implements ECA action code generator plugin.
 */
class ActionGenerator extends ModuleGenerator {

  /**
   * The name of the generator.
   *
   * @var string
   */
  protected string $name = 'plugin:eca:action';

  /**
   * The description.
   *
   * @var string
   */
  protected string $description = 'Generates an ECA action.';

  /**
   * The command alias.
   *
   * @var string
   */
  protected string $alias = 'eca-action';

  /**
   * The path to the twig template.
   *
   * @var string
   */
  protected string $templatePath = __DIR__ . '/action';

  /**
   * {@inheritdoc}
   */
  protected function generate(&$vars): void {
    $this->collectDefault($vars);
    $vars['purpose'] = $this->ask('Purpose of the action (typically 1 to 3 words)', 'Print object');
    $vars['description'] = $this->ask('Description', '');
    $vars['type'] = $this->ask('Type (e.g. "entity" or "node" or "user", normally empty)', '');
    $vars['options'] = $this->confirm('Supports drop-down list for configuration', FALSE);
    $vars['id'] = mb_strtolower(str_replace([':', ' ', '-', '.', ',', '__'], '_', $vars['purpose']));
    $vars['class'] = '{id|camelize}Action';
    $this->addFile('src/Plugin/Action/{class}.php', 'plugin.twig');
  }

}
