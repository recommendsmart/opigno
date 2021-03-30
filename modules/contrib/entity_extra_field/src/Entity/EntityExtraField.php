<?php

namespace Drupal\entity_extra_field\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\ConfigEntityType;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\entity_extra_field\ExtraFieldTypePluginInterface;

/**
 * Define entity extra field.
 *
 * @ConfigEntityType(
 *   id = "entity_extra_field",
 *   label = @Translation("Extra Field"),
 *   admin_permission = "administer entity extra field",
 *   config_prefix = "extra_field",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   handlers = {
 *     "form" = {
 *       "add" = "\Drupal\entity_extra_field\Form\EntityExtraFieldForm",
 *       "edit" = "\Drupal\entity_extra_field\Form\EntityExtraFieldForm",
 *       "delete" = "\Drupal\entity_extra_field\Form\EntityExtraFieldFormDelete"
 *     },
 *     "list_builder" = "\Drupal\entity_extra_field\Controller\EntityExtraFieldListBuilder"
 *   }
 * )
 */
class EntityExtraField extends ConfigEntityBase implements EntityExtraFieldInterface {

  /**
   * @var string
   */
  public $id;

  /**
   * @var string
   */
  public $name;

  /**
   * @var string
   */
  public $label;

  /**
   * @var string
   */
  public $description;

  /**
   * @var array
   */
  public $display = [];

  /**
   * @var string
   */
  public $field_type_id;

  /**
   * @var bool
   */
  public $display_label = FALSE;

  /**
   * @var array
   */
  public $field_type_config = [];

  /**
   * @var array
   */
  public $field_type_condition = [];

  /**
   * @var string
   */
  public $base_entity_type_id;

  /**
   * @var string
   */
  public $base_bundle_type_id;

  /**
   * @var array
   */
  protected $build_attachments = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    if (empty($this->name)
      || empty($this->base_entity_type_id)
      || empty($this->base_bundle_type_id)) {
      return NULL;
    }

    return "{$this->base_entity_type_id}.{$this->base_bundle_type_id}.{$this->name}";
  }

  /**
   * {@inheritdoc}
   */
  public function name() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function displayLabel() {
    return $this->display_label;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplay() {
    return $this->display;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayType() {
    $display = $this->getDisplay();

    return isset($display['type'])
      ? $display['type']
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTypeLabel() {
    return $this->getFieldTypePlugin()->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTypePluginId() {
    return $this->field_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTypePluginConfig() {
    return $this->field_type_config;
  }

  /**
   * {@inheritDoc}
   */
  public function getFieldTypeCondition() {
    return $this->field_type_condition;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseEntityTypeId() {
    return $this->base_entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseBundleTypeId() {
    return $this->base_bundle_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseEntityType() {
    return $this->entityTypeManager()->getDefinition(
      $this->getBaseEntityTypeId()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseEntityTypeBundle() {
    $entity_type = $this->getBaseEntityType();

    return $this->entityTypeManager()->getDefinition(
      $entity_type->getBundleEntityType()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheDiscoveryId() {
    $langcode = $this->languageManager()->getCurrentLanguage()->getId();
    return "entity_bundle_extra_fields:{$this->getBaseEntityTypeId()}:{$this->getBaseBundleTypeId()}:{$langcode}";
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheRenderTag() {
    return "entity_extra_field:{$this->getDisplayType()}.{$this->getBaseEntityTypeId()}.{$this->getBaseBundleTypeId()}";
  }

  /**
   * {@inheritDoc}
   */
  public function getBuildAttachments() {
    return $this->build_attachments;
  }

  /**
   * {@inheritDoc}
   */
  public function getActiveFieldTypeConditions() {
    return array_filter($this->getFieldTypeCondition(), function ($value) {
      unset($value['id'], $value['negate']);
      return !empty(array_filter($value));
    });
  }

  /**
   * {@inheritDoc}
   */
  public function setBuildAttachment($type, array $attachment) {
    if (!isset($this->build_attachments[$type])) {
      $this->build_attachments[$type] = [];
    }

    $this->build_attachments[$type] = array_replace_recursive(
      $this->build_attachments[$type], $attachment
    );

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function build(EntityInterface $entity, EntityDisplayInterface $display) {
    $field_type_plugin = $this->getFieldTypePlugin();

    if (!$field_type_plugin instanceof ExtraFieldTypePluginInterface) {
      return [];
    }

    return [
      '#field' => $this,
      '#theme' => 'entity_extra_field',
      'label' => [
        '#plain_text' => $this->displayLabel()
          ? $this->label()
          : NULL
      ],
      'content' => $field_type_plugin->build($entity, $display)
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function hasDisplayComponent(EntityDisplayInterface $display) {
    return $display->getComponent($this->name()) !== NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function hasConditionsBeenMet(array $contexts, $all_must_pass = FALSE) {
    $conditions = $this->getActiveFieldTypeConditions();

    if (empty($conditions)) {
      return TRUE;
    }
    $verdicts = [];

    foreach ($this->getActiveFieldTypeConditions() as $plugin_id => $configuration) {
      /** @var \Drupal\Core\Condition\ConditionPluginBase $condition */
      $condition = $this->conditionPluginManager()
        ->createInstance($plugin_id, $configuration);

      if ($context_definitions = $condition->getContextDefinitions()) {
        $condition_contexts = array_intersect_key($contexts, $context_definitions);

        foreach ($condition_contexts as $name => $context) {
          $condition->setContextValue($name, $context);
        }
      }
      $verdict = $condition->evaluate();

      if ($verdict && !$all_must_pass) {
        return TRUE;
      }
      $verdicts[] = $verdict;
    }
    $verdicts = array_unique($verdicts);

    return count($verdicts) === 1 && current($verdicts) === TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    return (bool) $this->getQuery()
      ->condition('id', "{$this->getBaseEntityTypeId()}.{$this->getBaseBundleTypeId()}.{$name}")
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'edit-form', array $options = []) {
    $base_route_name = $this->getBaseRouteName();
    $route_parameters = $this->urlRouteParameters($rel);

    switch ($rel) {
      case 'collection':
        return URL::fromRoute($base_route_name, $route_parameters, $options);
      case 'add-form':
        return Url::fromRoute("{$base_route_name}.add", $route_parameters, $options);
      case 'edit-form':
        return Url::fromRoute("{$base_route_name}.edit", $route_parameters, $options);
      case 'delete-form':
        return Url::fromRoute("{$base_route_name}.delete", $route_parameters, $options);
    }

    throw new \RuntimeException(
      sprintf('Unable to find %s to built a URL.', $rel)
    );
  }

  /**
   * {@inheritDoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    if ($field_type_plugin = $this->getFieldTypePlugin()) {
      $this->calculatePluginDependencies($field_type_plugin);
    }

    return $this;
  }

  /**
   * Get field type plugin instance.
   *
   * @return ExtraFieldTypePluginInterface
   *   The extra field type plugin.
   */
  protected function getFieldTypePlugin() {
    return \Drupal::service('plugin.manager.extra_field_type')
      ->createInstance($this->getFieldTypePluginId(), $this->getFieldTypePluginConfig());
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $base_bundle_type_id = $this->getBaseEntityTypeBundle()->id();

    $uri_route_parameters = [];
    $uri_route_parameters[$base_bundle_type_id] = $this->getBaseBundleTypeId();

    switch ($rel) {
      case 'edit-form':
      case 'delete-form':
        $uri_route_parameters[$this->getEntityTypeId()] = $this->id();
        break;
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  protected function linkTemplates() {
    $templates = [];
    $ui_base_path = $this->getBaseEntityBundleUiPath();

    $entity_type = $this->getEntityType();
    $entity_handlers = $entity_type->getHandlerClasses();

    if (isset($entity_handlers['form'])) {
      foreach (array_keys($entity_handlers['form']) as $rel) {
        $template_path = "{$ui_base_path}/extra-fields";

        switch ($rel) {
          case 'add':
            $template_path = "{$template_path}/{$rel}";
            break;
          case 'edit':
          case 'delete':
            $template_path = "{$template_path}/{" . $entity_type->id() . "}/{$rel}";
            break;
        }
        $templates[$rel . '-form'] = $template_path;
      }
    }
    $templates['collection'] = "{$ui_base_path}/extra-fields";

    return $templates;
  }

  /**
   * Get base entity bundle UI path.
   *
   * @return bool|string|null
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getBaseEntityBundleUiPath() {
    $base_route = $this
      ->getBaseEntityType()
      ->get('field_ui_base_route');

    if (!isset($base_route)) {
      return NULL;
    }

    $base_route_rel = strtr(
      substr($base_route, strrpos($base_route, '.') + 1),
      ['_' => '-']
    );
    $base_entity_bundle = $this->getBaseEntityTypeBundle();

    if (!$base_entity_bundle->hasLinkTemplate($base_route_rel)) {
      return NULL;
    }

    return $base_entity_bundle->getLinkTemplate($base_route_rel);
  }

  /**
   * Get base entity route name.
   *
   * @return string
   *   The base entity route.
   */
  protected function getBaseRouteName() {
    return "entity.{$this->getBaseEntityTypeId()}.extra_fields";
  }

  /**
   * Get entity storage query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getQuery() {
    return $this->getStorage()->getQuery();
  }

  /**
   * Get entity storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  protected function getStorage() {
    return $this->entityTypeManager()
      ->getStorage($this->getEntityTypeId());
  }

  /**
   * Condition plugin manager service.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The condition plugin manager service.
   */
  protected function conditionPluginManager() {
    return \Drupal::service('plugin.manager.condition');
  }
}
