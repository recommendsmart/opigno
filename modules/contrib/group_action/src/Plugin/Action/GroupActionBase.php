<?php

namespace Drupal\group_action\Plugin\Action;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\Token;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Group-related actions.
 */
abstract class GroupActionBase extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The plugin manager of group content enablers.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected GroupContentEnablerManagerInterface $gcePluginManager;

  /**
   * The Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $token;

  /**
   * Constructs a new GroupActionBase plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $gce_plugin_manager
   *   The plugin manager of group content enablers.
   * @param \Drupal\Core\Utility\Token $token
   *   The Token service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, GroupContentEnablerManagerInterface $gce_plugin_manager, Token $token, TranslationInterface $string_translation) {
    parent::__construct($configuration + ['values' => []], $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->gcePluginManager = $gce_plugin_manager;
    $this->token = $token;
    $this->stringTranslation = $string_translation;
    if (is_string($this->configuration['values'])) {
      $this->configuration['values'] = $this->decodeValues($this->configuration['values']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.group_content_enabler'),
      $container->get('token'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'operation' => '', // Either one of "create" or "delete".
      'content_plugin' => '', // The group content plugin ID.
      'group_id' => '', // The group ID. Can be numerical or a UUID.
      'entity_id' => '', // The entity ID. Can be numerical or a UUID.
      'values' => '', // Raw field values. Must be resolved to a flat array.
      //'add_method' => '', // Only relevant for "create" operation.
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $default_config = $this->defaultConfiguration();
    if ($default_config['operation'] === '') {
      $form['operation'] = [
        '#type' => 'select',
        '#title' => $this->t('Operation'),
        '#options' => ['create' => $this->t('Add'), 'delete' => $this->t('Remove')],
        '#default_value' => $this->configuration['operation'] ?? 'create',
        '#required' => TRUE,
      ];
    }
    if ($default_config['content_plugin'] === '') {
      $plugin_options = [
        '_none' => $this->t('- Select -'),
      ];
      foreach ($this->gcePluginManager->getDefinitions() as $id => $definition) {
        $plugin_options[$id] = $definition['label'];
      }
      $form['content_plugin'] = [
        '#type' => 'select',
        '#title' => $this->t('Content plugin'),
        '#options' => $plugin_options,
        '#default_value' => $this->configuration['content_plugin'] ?? '_none',
        '#required' => TRUE,
        '#empty_value' => '_none',
      ];
    }
    if ($default_config['group_id'] === '') {
      $form['group_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Group ID / UUID'),
        '#description' => $this->t('The numerical or universally unique ID of the group. This field supports tokens.'),
        '#default_value' => $this->configuration['group_id'] ?? '',
        '#required' => TRUE,
      ];
    }
    if ($default_config['entity_id'] === '') {
      $form['entity_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Entity ID / UUID'),
        '#description' => $this->t('The entity ID. Supports tokens. Leave blank to use the entity this action will operate on.'),
        '#default_value' => $this->configuration['entity_id'] ?? '',
      ];
    }
    if ($default_config['values'] === '') {
      $values = $this->configuration['values'] ?? [];
      if (is_string($values)) {
        $values = $this->decodeValues($values);
      }
      $values_string = '';
      foreach ($values as $k => $vs) {
        if (!is_array($vs)) {
          $vs = [$vs];
          $values[$k] = $vs;
        }
        foreach ($vs as $v) {
          $values_string .= "${k}: ${v}\n";
        }
      }
      $form['values'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Group content values'),
        '#default_value' => $values_string,
        '#description' => $this->t('A key-value list of raw field values to set for the Group content. Supports tokens. Set one value per line. Example:<em><br/>field_mynumber: 1<br/>group_roles: mygroup-myrole1<br/>group_roles: mygroup-myrole2</em>'),
      ];
    }
    if ((($this->configuration['operation'] ?? 'create') === 'create') && ($this->configuration['content_plugin'] ?? 'group_membership') !== 'group_membership') {
      $form['add_method'] = [
        '#type' => 'select',
        '#title' => $this->t('How to add'),
        '#options' => [
          'skip_existing' => $this->t('Only add when not yet added'),
          'always_add' => $this->t('Always add, no matter if already added'),
        ],
        '#required' => TRUE,
        '#default_value' => $this->configuration['add_method'] ?? 'skip_existing',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo Validate raw values.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $default_config = $this->defaultConfiguration();
    if ($default_config['operation'] === '') {
      $this->configuration['operation'] = $form_state->getValue('operation');
    }
    if ($default_config['group_id'] === '') {
      $this->configuration['group_id'] = $form_state->getValue('group_id');
    }
    if ($default_config['entity_id'] === '') {
      $this->configuration['entity_id'] = $form_state->getValue('entity_id');
    }
    if ($default_config['content_plugin'] === '') {
      $this->configuration['content_plugin'] = $form_state->getValue('content_plugin');
    }
    if ($default_config['values'] === '') {
      $this->configuration['values'] = $this->decodeValues($form_state->getValue('values', ''));
    }
    if (($this->configuration['operation'] === 'create') && $form_state->hasValue('add_method')) {
      $this->configuration['add_method'] = $form_state->getValue('add_method');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (is_null($account)) {
      $account = \Drupal::currentUser()->getAccount();
    }
    $entity = $this->loadEntity($object);
    $group = $this->loadGroup($entity);
    $content_plugin_id = $this->configuration['content_plugin'];
    $operation = $this->configuration['operation'];
    $result = $group ? AccessResult::allowedIf($group->hasPermission("$operation $content_plugin_id content", $account))->addCacheableDependency($group) : AccessResult::allowed();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity = $this->loadEntity($entity);
    if (!($entity instanceof EntityInterface) || !($group = $this->loadGroup($entity))) {
      return;
    }
    $content_plugin_id = $this->configuration['content_plugin'];
    if (!in_array($content_plugin_id, $this->gcePluginManager->getInstalledIds($group->getGroupType()))) {
      return;
    }
    $content_plugin_definition = $this->gcePluginManager->getDefinition($content_plugin_id);
    $entity_type_id = $content_plugin_definition['entity_type_id'];
    if ($entity_type_id !== $entity->getEntityTypeId()) {
      return;
    }
    if (!empty($content_plugin_definition['entity_bundle']) && ($content_plugin_definition['entity_bundle'] !== $entity->bundle())) {
      return;
    }
    $operation = $this->configuration['operation'];

    $values = $this->configuration['values'] ?? [];
    $values_contain_token = FALSE;
    foreach ($values as $k => $vs) {
      if (!is_array($vs)) {
        $vs = [$vs];
        $values[$k] = $vs;
      }
      foreach ($vs as $v) {
        if (!empty($v) && strpos($v, '[') !== FALSE) {
          $values_contain_token = TRUE;
          break 2;
        }
      }
    }
    if ($values_contain_token) {
      $token_data = [];
      $token_data[$entity->getEntityTypeId()] = $entity;
      if (!($entity instanceof GroupInterface)) {
        $token_data['group'] = $group;
      }
      $token_options = ['clear' => TRUE];
      foreach ($values as $k => $vs) {
        if (!is_array($vs)) {
          $vs = [$vs];
          $values[$k] = $vs;
        }
        foreach ($vs as $i => $v) {
          $v = trim((string) $this->token->replace($v, $token_data, $token_options));
          if ($v === '') {
            unset($values[$k][$i]);
          }
          else {
            $values[$k][$i] = $v;
          }
        }
      }
    }
    foreach ($values as $k => $vs) {
      if (is_array($vs) && (count($vs) === 1)) {
        $values[$k] = reset($vs);
      }
    }

    if ($operation === 'create') {
      $may_add = TRUE;
      if ((($this->configuration['add_method'] ?? 'skip_existing') === 'skip_existing') && (($this->configuration['content_plugin'] ?? 'group_membership') !== 'group_membership')) {
        $may_add = empty($group->getContent($content_plugin_id, ['entity_id' => $entity->id()]));
      }
      if ($may_add) {
        $group->addContent($entity, $content_plugin_id, $values);
      }
    }
    elseif (($operation === 'delete') && !$entity->isNew()) {
      foreach ($group->getContent($content_plugin_id, ['entity_id' => $entity->id()]) as $group_content) {
        $group_content->delete();
      }
    }
  }

  /**
   * Loads the group using the plugin configuration.
   *
   * @param mixed $entity
   *   (Optional) The entity upon the action is to be executed.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The loaded group, or NULL if no group could be loaded.
   */
  protected function loadGroup($entity = NULL): ?GroupInterface {
    $group_id = $this->configuration['group_id'] ?? '';
    if (!empty($group_id) && strpos($group_id, '[') !== FALSE) {
      $token_data = [];
      if ($entity instanceof EntityInterface) {
        $token_data[$entity->getEntityTypeId()] = $entity;
      }
      $token_options = ['clear' => TRUE];
      $group_id = (string) $this->token->replace($group_id, $token_data, $token_options);
    }
    $group_id = trim($group_id);
    if ($group_id === '') {
      return NULL;
    }
    if (ctype_digit($group_id)) {
      return $this->entityTypeManager->getStorage('group')->load($group_id);
    }
    if (Uuid::isValid($group_id)) {
      /** @var \Drupal\Core\Entity\EntityRepositoryInterface $repository */
      $repository = \Drupal::service('entity.repository');
      return $repository->loadEntityByUuid('group', $group_id);
    }
    return NULL;
  }

  /**
   * Load the entity using the plugin configuration.
   *
   * @param mixed $entity
   *   (Optional) The entity upon the action is to be executed.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity or NULL if the entity could not be resolved.
   */
  protected function loadEntity($entity = NULL): ?EntityInterface {
    $entity_id = $this->configuration['entity_id'] ?? '';
    if (!empty($entity_id)) {
      $token_data = [];
      if ($entity instanceof EntityInterface) {
        $token_data[$entity->getEntityTypeId()] = $entity;
      }
      $token_options = ['clear' => TRUE];
      $entity_id = (string) $this->token->replace($entity_id, $token_data, $token_options);
    }
    $entity_id = trim($entity_id);
    if ($entity_id === '') {
      return $entity;
    }
    $content_plugin_definition = $this->gcePluginManager->getDefinition($this->configuration['content_plugin']);
    $entity_type_id = $content_plugin_definition['entity_type_id'];
    if (Uuid::isValid($entity_id)) {
      /** @var \Drupal\Core\Entity\EntityRepositoryInterface $repository */
      $repository = \Drupal::service('entity.repository');
      return $repository->loadEntityByUuid($entity_type_id, $entity_id);
    }
    return $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
  }

  protected function decodeValues(string $values_string): array {
    $values = [];
    $tok = strtok($values_string, "\n");
    while ($tok !== false) {
      [$k, $v] = array_merge(explode(':', $tok, 2), ['']);
      if (trim($k) !== '') {
        $values[trim($k)][] = trim($v);
      }
      $tok = strtok("\n");
    }
    return $values;
  }

}
