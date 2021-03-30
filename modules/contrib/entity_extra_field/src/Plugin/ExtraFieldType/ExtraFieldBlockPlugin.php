<?php

namespace Drupal\entity_extra_field\Plugin\ExtraFieldType;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Utility\Token;
use Drupal\entity_extra_field\Annotation\ExtraFieldType;
use Drupal\entity_extra_field\ExtraFieldTypeBase;
use Drupal\entity_extra_field\ExtraFieldTypePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define extra field block type.
 *
 * @ExtraFieldType(
 *   id = "block",
 *   label = @Translation("Block")
 * )
 */
class ExtraFieldBlockPlugin extends ExtraFieldTypePluginBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $blockManager;

  /**
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * Extra field block plugin constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param $plugin_id
   *   The plugin identifier.
   * @param $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager service.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler service.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context repository service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Token $token,
    ModuleHandlerInterface $module_handler,
    RouteMatchInterface $current_route_match,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    BlockManagerInterface $block_manager,
    ContextHandlerInterface $context_handler,
    ContextRepositoryInterface $context_repository
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $token,
      $module_handler,
      $current_route_match,
      $entity_type_manager,
      $entity_field_manager
    );
    $this->blockManager = $block_manager;
    $this->contextHandler = $context_handler;
    $this->contextRepository = $context_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token'),
      $container->get('module_handler'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.block'),
      $container->get('context.handler'),
      $container->get('context.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'block_type' => NULL,
      'block_config' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form_state->setTemporaryValue(
      'gathered_contexts',
      $this->contextRepository->getAvailableContexts()
    );
    $block_type = $this->getPluginFormStateValue('block_type', $form_state);

    $form['block_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Block Type'),
      '#required' => TRUE,
      '#options' => $this->getBlockTypeOptions(),
      '#empty_option' => $this->t('- Select -'),
      '#ajax' => [
        'event' => 'change',
        'method' => 'replace',
      ] + $this->extraFieldPluginAjax(),
      '#default_value' => $block_type,
    ];

    if (isset($block_type) && !empty($block_type)) {
      if ($this->blockManager->hasDefinition($block_type)) {
        $block_config = $this->getPluginFormStateValue('block_config', $form_state, []);
        $block_instance = $this->blockManager->createInstance($block_type, $block_config);

        if ($block_instance instanceof PluginFormInterface) {
          $form['block_config'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Block Configuration'),
            '#tree' => TRUE,
          ];
          $subform = ['#parents' => array_merge(
            $form['#parents'], ['block_config']
          )];

          $form['block_config'] += $block_instance->buildConfigurationForm(
            $subform,
            SubformState::createForSubform($subform, $form, $form_state)
          );
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $block_instance = $this->getBlockTypeInstance();

    if ($block_instance !== FALSE) {
      if ($block_instance instanceof PluginFormInterface) {
        $subform = ['#parents' => array_merge(
          $form['#parents'], ['block_config']
        )];

        $block_instance->validateConfigurationForm(
          $subform,
          SubformState::createForSubform($subform, $form, $form_state)
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $block_instance = $this->getBlockTypeInstance();

    if ($block_instance !== FALSE) {
      if ($block_instance instanceof PluginFormInterface) {
        $subform = ['#parents' => array_merge(
          $form['#parents'], ['block_config']
        )];

        $block_instance->submitConfigurationForm(
          $subform,
          SubformState::createForSubform($subform, $form, $form_state)
        );

        $form_state->setValue(
          ['block_config'],
          $block_instance->getConfiguration()
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(EntityInterface $entity, EntityDisplayInterface $display) {
    $block = $this->getBlockTypeInstance();

    if (FALSE === $block) {
      return [];
    }

    if ($block instanceof ContextAwarePluginInterface) {
      try {
        if ($context_mapping = $block->getContextMapping()) {
          $contexts = $this->contextRepository->getRuntimeContexts(
            array_values($context_mapping)
          );
          $this->contextHandler->applyContextMapping($block, $contexts);
        }
      } catch (\Exception $exception) {
        watchdog_exception('entity_extra_field', $exception);
      }
    }

    return $block->build();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    if ($block_type_instance = $this->getBlockTypeInstance()) {
      $this->calculatePluginDependencies($block_type_instance);
    }

    return parent::calculateDependencies();
  }

  /**
   * Get block type instance.
   *
   * @return bool|\Drupal\Core\Block\BlockBase
   *   The block instance; otherwise FALSE if type is not defined.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getBlockTypeInstance() {
    $config = $this->getConfiguration();

    if (!isset($config['block_type'])) {
      return FALSE;
    }

    return $this->blockManager->createInstance(
      $config['block_type'],
      $config['block_config']
    );
  }
  
  /**
   * Get block type options.
   *
   * @param array $excluded_ids
   *   An array of block ids to exclude.
   *
   * @return array
   *   An array of block type options.
   */
  protected function getBlockTypeOptions($excluded_ids = []) {
    $options = [];

    // There are a couple block ids that are excluded by default as either
    // they're not really needed, or they are causing problems when selected.
    $excluded_ids = [
      'broken',
      'system_branding_block',
    ] + $excluded_ids;

    foreach ($this->blockManager->getDefinitions() as $block_id => $definition) {
      if (!isset($definition['admin_label']) || in_array($block_id, $excluded_ids)) {
        continue;
      }
      $category = isset($definition['category'])
        ? $definition['category']
        : $this->t('Undefined');

      $options[(string)$category][$block_id] = $definition['admin_label'];
    }

    return $options;
  }
}
