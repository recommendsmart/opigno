<?php

namespace Drupal\lbl;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Layout\Icon\IconBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base layout class for baumeister.
 */
class BaseLayout extends LayoutDefault implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Icon builder.
   *
   * @var \Drupal\Core\Layout\Icon\IconBuilderInterface
   */
  protected $iconBuilder;

  /**
   * Icon builder.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Css generator manager.
   *
   * @var \Drupal\lbl\CssGeneratorInterface
   */
  protected $cssGenerator;

  /**
   * {@inheritdoc}
   */
  final public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    IconBuilderInterface $icon_builder,
    ConfigFactoryInterface $config_factory,
    CssGeneratorInterface $css_generator
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->iconBuilder = $icon_builder;
    $this->cssGenerator = $css_generator;
    $this->config = $config_factory;
    $this->iconBuilder->setHeight(25);
    $this->iconBuilder->setWidth(25);
    $this->iconBuilder->setPadding(1);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('layout.icon_builder'),
      $container->get('config.factory'),
      $container->get(CssGenerator::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'breakpoints' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['label']['#access'] = FALSE;

    $configuration = $this->getConfiguration();

    $definitions = $this->getPluginDefinition()->get('variants');
    $breakpoints = $this->cssGenerator->getBreakpoints();

    if (count($breakpoints) == 0 || !$definitions || !is_array($definitions) || count($definitions) == 0) {
      return $form;
    }

    $form['#attached']['library'][] = 'lbl/form';
    $form['breakpoints'] = [
      '#type' => 'details',
      '#title' => $this->t('Breakpoints'),
      '#open' => FALSE,
    ];
    $renderer = \Drupal::service('renderer');

    /** @var \Drupal\breakpoint\Breakpoint $breakpoint */
    foreach ($breakpoints as $breakpoint) {
      $breakPointPluginId = str_replace('.', '-', $breakpoint->getPluginId());
      $form['breakpoints'][$breakPointPluginId] = [
        '#type' => 'fieldset',
        '#title' => '<h5>' . $breakpoint->getLabel() . '</h5>',
        '#attributes' => ['class' => ['lbl-checkbox-selector']],
      ];
      $options = [];
      foreach ($definitions as $key => $def) {
        $options[$key] = $renderer->render($this->iconBuilder->build($def['map']));
      }
      $form['breakpoints'][$breakPointPluginId]['config'] = [
        '#type' => 'radios',
        '#options' => $options,
        '#default_value' => ($configuration['breakpoints'][$breakPointPluginId]) ? $configuration['breakpoints'][$breakPointPluginId] : NULL,
      ];
    }
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $definition = $this->getPluginDefinition();
    $id = $definition->id();
    $build['#settings']['section_attributes'] = new Attribute();
    $build['#settings']['section_attributes']['class'] = ['lbl-layout'];

    foreach ($this->getPluginDefinition()->getRegionNames() as $region_name) {
      if (array_key_exists($region_name, $regions)) {
        $build[$region_name]['#attributes']['style'] = 'grid-area: ' . $region_name;
      }
    }

    $css = $this->cssGenerator->build($definition);

    $build['#attached']['html_head'] = [
      [
        [
          '#tag' => 'style',
          '#value' => $css,
        ],
        $id,
      ],
    ];

    $build['#attached']['library'][] = 'lbl/layout';

    if ($this->configuration['breakpoints']) {
      foreach ($this->configuration['breakpoints'] as $break => $breakpoint) {
        $build['#settings']['section_attributes']['class'][] =
          Html::cleanCssIdentifier(implode('-', [$id, $breakpoint, $break]));
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $breakpoints = $form_state->getValue('breakpoints');
    $configs = [];
    foreach ($breakpoints as $breakpoint => $config) {
      $configs[$breakpoint] = $config['config'];
    }
    $this->configuration['breakpoints'] = $configs;
  }

}
