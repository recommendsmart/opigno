<?php

namespace Drupal\eca_tamper\Plugin\Action;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\eca\EcaState;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca\Token\TokenInterface;
use Drupal\eca\Service\Conditions;
use Drupal\tamper\Exception\SkipTamperDataException;
use Drupal\tamper\Exception\SkipTamperItemException;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\SourceDefinition;
use Drupal\tamper\TamperInterface;
use Drupal\tamper\TamperManagerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide all tamper plugins as ECA actions.
 *
 * @Action(
 *   id = "eca_tamper",
 *   deriver = "Drupal\eca_tamper\Plugin\Action\TamperDeriver"
 * )
 */
class Tamper extends ConfigurableActionBase {

  /**
   * The tamper plugin manager.
   *
   * @var \Drupal\tamper\TamperManagerInterface
   */
  protected TamperManagerInterface $tamperManager;

  /**
   * The tamper plugin.
   *
   * @var \Drupal\tamper\TamperInterface
   */
  protected TamperInterface $tamperPlugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, TokenInterface $token_services, AccountProxyInterface $current_user, TimeInterface $time, EcaState $state, TamperManagerInterface $tamper_manager) {
    $this->tamperManager = $tamper_manager;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $token_services, $current_user, $time, $state);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ActionBase {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('eca.token_services'),
      $container->get('current_user'),
      $container->get('datetime.time'),
      $container->get('eca.state'),
      $container->get('plugin.manager.tamper')
    );
  }

  /**
   * Return the tamper plugin after it has been fully configured.
   *
   * @return \Drupal\tamper\TamperInterface
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function tamperPlugin(): TamperInterface {
    if (!isset($this->tamperPlugin)) {
      /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
      $this->tamperPlugin = $this->tamperManager->createInstance($this->pluginDefinition['tamper_plugin'], ['source_definition' => new SourceDefinition([])]);

      $configuration = $this->configuration;
      unset($configuration['eca_data'], $configuration['eca_token_name']);
      foreach ($this->defaultConfiguration() as $key => $value) {
        if (is_bool($value) && isset($configuration[$key])) {
          $configuration[$key] = $configuration[$key] === Conditions::OPTION_YES;
        }
      }
      $this->tamperPlugin->setConfiguration($configuration);
    }
    return $this->tamperPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $data = $this->tokenServices->replaceClear($this->configuration['eca_data']);
    try {
      $value = $this->tamperPlugin()->tamper($data);
    }
    catch (PluginException|SkipTamperDataException|TamperException|SkipTamperItemException $e) {
      $value = $data;
    }

    $this->tokenServices->addTokenData($this->configuration['eca_token_name'], $value);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    try {
      $pluginDefault = $this->tamperPlugin()->defaultConfiguration();
    }
    catch (PluginException $e) {
      $pluginDefault = [];
    }
    return [
        'eca_data' => '',
        'eca_token_name' => '',
      ] +
      $pluginDefault +
      parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['eca_data'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Data'),
      '#default_value' => $this->configuration['eca_data'],
      '#weight' => -10,
    ];
    $form['eca_token_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token name'),
      '#default_value' => $this->configuration['eca_token_name'],
      '#weight' => -9,
    ];

    try {
      return $this->tamperPlugin()->buildConfigurationForm($form, $form_state);
    }
    catch (PluginException $e) {
      // @todo: Do we need to log this?
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['eca_data'] = $form_state->getValue('eca_data');
    $this->configuration['eca_token_name'] = $form_state->getValue('eca_token_name');
    parent::submitConfigurationForm($form, $form_state);
    try {
      $this->tamperPlugin()->submitConfigurationForm($form, $form_state);
    }
    catch (PluginException $e) {
      // @todo: Do we need to log this?
    }
  }

}
