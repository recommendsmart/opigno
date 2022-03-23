<?php

namespace Drupal\eca\Entity;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\eca\Entity\Objects\EcaEvent;
use Drupal\eca\Entity\Objects\EcaGateway;
use Drupal\eca\Entity\Objects\EcaObject;
use Drupal\eca\Entity\Objects\EcaAction;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\eca\Plugin\ECA\Modeller\ModellerInterface;

/**
 * Defines the ECA entity type.
 *
 * @ConfigEntityType(
 *   id = "eca",
 *   label = @Translation("ECA"),
 *   label_collection = @Translation("ECAs"),
 *   label_singular = @Translation("ECA"),
 *   label_plural = @Translation("ECAs"),
 *   label_count = @PluralTranslation(
 *     singular = "@count ECA",
 *     plural = "@count ECAs",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "delete" = "Drupal\eca\Form\EcaDeleteForm"
 *     },
 *     "storage" = "Drupal\eca\Entity\EcaStorage",
 *     "list_builder" = "Drupal\eca\Entity\ListBuilder",
 *     "access" = "Drupal\eca\Entity\AccessControlHandler"
 *   },
 *   config_prefix = "eca",
 *   admin_permission = "administer eca",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config//workflow/eca/{eca}/edit",
 *     "delete-form" = "/admin/config/workflow/eca/{eca}/delete",
 *     "collection" = "/admin/config/workflow/eca",
 *   },
 *   config_export = {
 *     "id",
 *     "modeller",
 *     "label",
 *     "uuid",
 *     "status",
 *     "version",
 *     "weight",
 *     "events",
 *     "conditions",
 *     "gateways",
 *     "actions"
 *   }
 * )
 */
class Eca extends ConfigEntityBase implements EntityWithPluginCollectionInterface {

  use EcaTrait;
  use EcaObjectTrait;

  /**
   * The ID.
   *
   * @var string
   */
  protected string $id;

  /**
   * The helpdesk label.
   *
   * @var string
   */
  protected string $label;

  /**
   * @var array
   */
  protected array $events;

  /**
   * @var array
   */
  protected array $conditions;

  /**
   * @var array
   */
  protected array $gateways;

  /**
   * @var array
   */
  protected array $actions;

  /**
   * @var \Drupal\eca\Entity\Model
   */
  protected Model $model;

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    if (!empty($this->events)) {
      $entity_field_info = [];
      foreach ($this->events as $component) {
        $this->addDependenciesFromComponent($component, $entity_field_info);
      }
    }
    return $this;
  }

  /**
   * Adds dependencies by reading from the given component.
   */
  protected function addDependenciesFromComponent(array $component, array &$entity_field_info) {
    $plugin_id_parts = !empty($component['plugin']) ? explode(':', $component['plugin']) : [];
    foreach ($plugin_id_parts as $id_part) {
      $this->addEntityFieldInfo($id_part, $entity_field_info);
    }
    if (!empty($component['fields'])) {
      $this->addDependenciesFromFields($component['fields'], $entity_field_info);
    }
    if (!empty($component['successors'])) {
      foreach ($component['successors'] as $successor) {
        if (empty($successor['id'])) {
          continue;
        }
        $successor_id = $successor['id'];
        foreach (['events', 'conditions', 'actions', 'gateways'] as $prop) {
          if (isset($this->$prop[$successor_id])) {
            $this->addDependenciesFromComponent($this->$prop[$successor_id], $entity_field_info);
            break;
          }
        }
      }
    }
  }

  /**
   * Adds dependencies from values of plugin config fields.
   *
   * @param array $fields
   *   The config fields of an ECA-related plugin (event / condition / action).
   * @param array &$entity_field_info
   *   An array of collected entity field info, keyed by entity type ID.
   *   This array will be expanded by further entity types that will be
   *   additionally found.
   */
  protected function addDependenciesFromFields(array $fields, array &$entity_field_info) {
    $variables = [];
    foreach ($fields as $name => $field) {
      if (!is_string($field)) {
        if (is_array($field)) {
          $this->addDependenciesFromFields($field, $entity_field_info);
        }
        continue;
      }

      if (mb_strpos('type', $name) !== FALSE) {
        list($field, $bundle) = array_merge(explode(' ', $field, 2), ['_all']);
      }
      else {
        preg_match_all('/
          [^\s\[\]\{\}:\.]+  # match type not containing whitespace : . [ ] { }
          [:\.]+             # separator (Token : or property path .)
          [^\s\[\]\{\}]+     # match name not containing whitespace [ ] { }
          /x', $field, $matches);
      }

      if (!isset($matches) || empty($matches[0])) {
        // Calling ::addEntityFieldInfo() here, so that the entity type is
        // present in case any subsequent plugin config field contains a field
        // name for that.
        $is_entity_type = $this->addEntityFieldInfo($field, $entity_field_info);
        if (!$is_entity_type && !in_array($field, $variables)) {
          $variables[] = $field;
        }
        elseif ($is_entity_type) {
          $entity_type_id = $field;
          if (isset($bundle) && $bundle !== '_all' && ($bundle_dependency = $this->entityTypeManager()->getDefinition($entity_type_id)->getBundleConfigDependency($bundle))) {
            $this->addDependency($bundle_dependency['type'], $bundle_dependency['name']);
          }
        }
      }
      else {
        foreach ($matches[0] as $variable) {
          if (!in_array($variable, $variables)) {
            $variables[] = $variable;
          }
        }
      }
    }
    foreach ($variables as $variable) {
      $variable_parts = mb_strpos($variable, ':') ? explode(':', $variable) : explode('.', $variable);
      foreach ($variable_parts as $variable_part) {
        if ($this->addEntityFieldInfo($variable_part, $entity_field_info)) {
          // Mapped to an entity type, thus no need for a field lookup.
          continue;
        }
        // Perform a lookup for used entity fields.
        /** @var \Drupal\Core\Entity\EntityFieldManager $field_manager */
        $field_manager = \Drupal::service('entity_field.manager');
        $field_config_storage = $this->entityTypeManager()->getStorage('field_config');
        $info_item = end($entity_field_info);
        while ($info_item) {
          $entity_type_id = key($entity_field_info);
          if (isset($info_item[$variable_part])) {
            $field_name = $variable_part;
            // Found an existing field, add its storage config as dependency.
            // No break of the loop here, because any entity type that
            // possibly holds a field with that name should be considered,
            // as we cannot determine the underlying entity type of Token
            // aliases in a bulletproof way.
            $this->addDependency('config', $info_item[$field_name]);
            // Include any field configuration from used bundles. Future
            // additions of fields and new bundles will be handled via hook
            // implementation.
            $bundles = array_keys($this->entityTypeBundleInfo()->getBundleInfo($entity_type_id));
            foreach ($bundles as $bundle) {
              $field_definitions = $field_manager->getFieldDefinitions($entity_type_id, $bundle);
              if (isset($field_definitions[$field_name])) {
                $field_config_id = $entity_type_id . '.' . $bundle . '.' . $field_name;
                /** @var \Drupal\field\FieldConfigInterface $field_config */
                if ($field_config = $field_config_storage->load($field_config_id)) {
                  $this->addDependency($field_config->getConfigDependencyKey(), 'field.field.' . $field_config->id());
                }
              }
            }
          }
          $info_item = prev($entity_field_info);
        }
      }
    }
  }

  /**
   * Expands the field info array if the given variable is an entity type ID.
   *
   * @param string $variable
   *   The variable that is or is not an entity type ID.
   * @param array &$entity_field_info
   *   The current list of entity field info, sorted in reverse order by found
   *   entity types.
   *
   * @return bool
   *   Returns TRUE if the given variable was resolved to an entity type ID.
   */
  protected function addEntityFieldInfo(string $variable, array &$entity_field_info): bool {
    if (!($entity_type_id = $this->token()->getEntityTypeForTokenType($variable))) {
      return FALSE;
    }
    $entity_type_manager = $this->entityTypeManager();
    if (isset($entity_field_info[$entity_type_id])) {
      // Put the info item at the end of the list, as we want to handle
      // found definitions by traversing in the reverse order they were found.
      $item = $entity_field_info[$entity_type_id];
      unset($entity_field_info[$entity_type_id]);
      $entity_field_info += [$entity_type_id => $item];
      return TRUE;
    }
    elseif ($entity_type_manager->hasDefinition($entity_type_id)) {
      $definition = $entity_type_manager->getDefinition($entity_type_id);
      $entity_field_info[$entity_type_id] = [];
      if ($definition->entityClassImplements(FieldableEntityInterface::class)) {
        /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
        $entity_field_manager = \Drupal::service('entity_field.manager');
        foreach ($entity_field_manager->getFieldStorageDefinitions($entity_type_id) as $field_name => $storage_definition) {
          // Base fields don't have a manageable storage configuration, thus
          // they are excluded here.
          if (!$storage_definition->isBaseField()) {
            $entity_field_info[$entity_type_id][$field_name] = "field.storage.$entity_type_id.$field_name";
          }
        }
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param $id
   *
   * @return string
   */
  protected function buildCacheId($id): string {
    return "eca:$this->id:$id";
  }

  /**
   * @return bool
   */
  public function isEditable(): bool {
    if ($modeller = $this->getModeller()) {
      return $modeller->isEditable();
    }
    return FALSE;
  }

  /**
   * @return bool
   */
  public function isExportable(): bool {
    if ($modeller = $this->getModeller()) {
      return $modeller->isExportable();
    }
    return FALSE;
  }

  /**
   * @return \Drupal\eca\Plugin\ECA\Modeller\ModellerInterface|null
   */
  public function getModeller(): ?ModellerInterface {
    try {
      /** @var ModellerInterface $plugin */
      $plugin = $this->modellerPluginManager()->createInstance($this->get('modeller'));
    }
    catch (PluginException $e) {
      $this->logger()->error($e->getMessage());
      return NULL;
    }
    if ($plugin !== NULL) {
      $plugin->setConfigEntity($this);
      return $plugin;
    }
    return NULL;
  }

  /**
   * @return \Drupal\eca\Entity\Model
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getModel(): Model {
    if (!isset($this->model)) {
      $storage = $this->entityTypeManager()->getStorage('eca_model');
      /** @var \Drupal\eca\Entity\Model $model */
      $model = $storage->load($this->id());
      if ($model === NULL) {
        $model = $storage->create([
          'id' => $this->id(),
        ]);
      }
      $this->model = $model;
    }
    return $this->model;
  }

  /**
   * @param string $id
   * @param string $plugin_id
   * @param array $fields
   *
   * @return $this
   */
  public function addCondition(string $id, string $plugin_id, array $fields): Eca {
    $this->conditions[$id] = [
      'plugin' => $plugin_id,
      'fields' => $fields,
    ];
    return $this;
  }

  /**
   * @param string $id
   * @param int $type
   * @param array $successors
   *
   * @return $this
   */
  public function addGateway(string $id, int $type, array $successors): Eca {
    $this->gateways[$id] = [
      'type' => $type,
      'successors' => $successors,
    ];
    return $this;
  }

  /**
   * @param string $id
   * @param string $plugin_id
   * @param string $label
   * @param array $fields
   * @param array $successors
   *
   * @return $this
   */
  public function addEvent(string $id, string $plugin_id, string $label, array $fields, array $successors): Eca {
    if (empty($label)) {
      $label = $id;
    }
    $this->events[$id] = [
      'plugin' => $plugin_id,
      'label' => $label,
      'fields' => $fields,
      'successors' => $successors,
    ];
    return $this;
  }

  /**
   * @param string $id
   * @param string $plugin_id
   * @param string $label
   * @param array $fields
   * @param array $successors
   *
   * @return $this
   */
  public function addAction(string $id, string $plugin_id, string $label, array $fields, array $successors): Eca {
    if (empty($label)) {
      $label = $id;
    }
    $this->actions[$id] = [
      'plugin' => $plugin_id,
      'label' => $label,
      'fields' => $fields,
      'successors' => $successors,
    ];
    return $this;
  }

  /**
   * @return \Drupal\eca\Entity\Objects\EcaEvent[]
   */
  public function getUsedEvents(): array {
    if ($cached = $this->memoryCache()->get($this->buildCacheId('events'))) {
      return $cached->data;
    }

    $events = [];
    foreach ($this->events as $id => $def) {
      if ($event = $this->getEcaObject('event', $def['plugin'], $id, $def['label'] ?? 'noname', $def['fields'], $def['successors'])) {
        $events[$id] = $event;
      }
    }
    $this->memoryCache()->set($this->buildCacheId('events'), $events, CacheBackendInterface::CACHE_PERMANENT, ['eca.memory_cache:' . $this->id]);
    return $events;
  }

  /**
   * @param \Drupal\eca\Entity\Objects\EcaObject $eca_object
   * @param \Drupal\Component\EventDispatcher\Event $event
   * @param array $context
   *
   * @return \Drupal\eca\Entity\Objects\EcaObject[]
   */
  public function getSuccessors(EcaObject $eca_object, Event $event, array $context): array {
    $successors = [];
    foreach ($eca_object->getSuccessors() as $successor) {
      $context['%successorlabel'] = $successor['label'] ?? 'noname';
      $context['%successorid'] = $successor['id'];
      if ($action = $this->actions[$successor['id']] ?? FALSE) {
        $this->logger()->debug('Check action successor %successorlabel (%successorid) from ECA %ecalabel (%ecaid) for event %event.', $context);
        if ($successorObject = $this->getEcaObject('action', $action['plugin'], $successor['id'], $successor['label'] ?? 'noname', $action['fields'], $action['successors'], $eca_object->getEvent())) {
          if ($this->conditionServices()->assertCondition($event, $successor['condition'], $this->conditions[$successor['condition']] ?? NULL, $context)) {
            $successors[] = $successorObject;
          }
        }
        else {
          $this->logger()->error('Invalid action successor %successorlabel (%successorid) from ECA %ecalabel (%ecaid) for event %event.', $context);
        }
      }
      else if ($gateway = $this->gateways[$successor['id']] ?? FALSE) {
        $this->logger()->debug('Check gateway successor %successorlabel (%successorid) from ECA %ecalabel (%ecaid) for event %event.', $context);
        $successorObject = new EcaGateway($this, $successor['id'], $successor['label'] ?? 'noname', $eca_object->getEvent(), $gateway['type']);
        $successorObject->setSuccessors($gateway['successors']);
        if ($this->conditionServices()->assertCondition($event, $successor['condition'], $this->conditions[$successor['condition']] ?? NULL, $context)) {
          $successors[] = $successorObject;
        }
      }
      else {
        $this->logger()->error('Non existant successor %successorlabel (%successorid) from ECA %ecalabel (%ecaid) for event %event.', $context);
      }
    }
    return $successors;
  }

  /**
   * @param string $type
   * @param string $plugin_id
   * @param string $id
   * @param string $label
   * @param array $fields
   * @param array $successors
   * @param \Drupal\eca\Entity\Objects\EcaEvent|null $event
   *
   * @return \Drupal\eca\Entity\Objects\EcaObject|null
   */
  private function getEcaObject(string $type, string $plugin_id, string $id, string $label, array $fields, array $successors, EcaEvent $event = NULL): ?EcaObject {
    $ecaObject = NULL;
    switch ($type) {
      case 'event':
        try {
          /** @var \Drupal\eca\Plugin\ECA\Event\EventInterface $plugin */
          $plugin = $this->eventPluginManager()->createInstance($plugin_id, $fields);
        }
        catch (PluginException $e) {
          // This can be ignored.
        }
        if (isset($plugin)) {
          $ecaObject = new EcaEvent($this, $id, $label, $plugin);
        }
        break;

      case 'action':
        if ($event !== NULL) {
          try {
            /** @var \Drupal\Core\Action\ActionInterface $plugin */
            $plugin = $this->actionPluginManager()->createInstance($plugin_id, $fields);
          } catch (PluginException $e) {
            // This can be ignored.
          }
          if (isset($plugin)) {
            $ecaObject = new EcaAction($this, $id, $label, $event, $plugin);
          }
        }
        break;

    }
    if ($ecaObject !== NULL) {
      foreach ($fields as $key => $value) {
        $ecaObject->setConfiguration($key, $value);
      }
      $ecaObject->setSuccessors($successors);
      return $ecaObject;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    $collections = [];
    if (!empty($this->events)) {
      foreach ($this->events as $id => $info) {
        if (empty($info['plugin'])) {
          continue;
        }
        $collections['events.' . $id] = new DefaultSingleLazyPluginCollection($this->eventPluginManager(), $info['plugin'], $info['fields'] ?? []);
      }
    }
    if (!empty($this->conditions)) {
      foreach ($this->conditions as $id => $info) {
        if (empty($info['plugin'])) {
          continue;
        }
        $collections['conditions.' . $id] = new DefaultSingleLazyPluginCollection($this->conditionPluginManager(), $info['plugin'], $info['fields'] ?? []);
      }
    }
    if (!empty($this->actions)) {
      foreach ($this->actions as $id => $info) {
        if (empty($info['plugin'])) {
          continue;
        }
        $collections['actions.' . $id] = new DefaultSingleLazyPluginCollection($this->actionPluginManager(), $info['plugin'], $info['fields'] ?? []);
      }
    }
    return $collections;
  }

  /**
   * Adds a dependency that could only be calculated on runtime.
   *
   * After adding a dependency on runtime, this configuration should be saved.
   *
   * @param string $type
   *   Type of dependency being added: 'module', 'theme', 'config', 'content'.
   * @param string $name
   *   If $type is 'module' or 'theme', the name of the module or theme. If
   *   $type is 'config' or 'content', the result of
   *   EntityInterface::getConfigDependencyName().
   *
   * @see \Drupal\Core\Entity\EntityInterface::getConfigDependencyName()
   *
   * @return static
   *   The ECA config itself.
   */
  public function addRuntimeDependency(string $type, string $name): Eca {
    $this->addDependency($type, $name);
    return $this;
  }

}
