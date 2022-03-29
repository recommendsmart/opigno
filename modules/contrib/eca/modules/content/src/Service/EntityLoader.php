<?php

namespace Drupal\eca_content\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\eca\Service\Conditions;
use Drupal\eca\Token\TokenInterface;

/**
 * Service for loading entities from ECA plugins.
 */
class EntityLoader {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The Token services.
   *
   * @var \Drupal\eca\Token\TokenInterface
   */
  protected TokenInterface $tokenServices;

  /**
   * Constructs a new EntityLoader object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\eca\Token\TokenInterface $token_services
   *   The Token services.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TokenInterface $token_services, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tokenServices = $token_services;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Provides default configuration values for plugins.
   *
   * @return array
   *   The default configuration values.
   */
  public function defaultConfiguration(): array {
    return [
      'from' => 'current',
      'entity_type' => '_none',
      'entity_id' => '',
      'revision_id' => '',
      'properties' => '',
      'langcode' => '_interface',
      'latest_revision' => FALSE,
      'unchanged' => FALSE,
    ];
  }

  /**
   * Builds up the configuration form elements for the given plugin config.
   *
   * @param array $plugin_configuration
   *   The plugin configuration.
   * @param array $form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form containing the elements.
   */
  public function buildConfigurationForm(array $plugin_configuration, array $form, FormStateInterface $form_state): array {
    $form['from'] = [
      '#type' => 'select',
      '#title' => $this->t('Load entity from'),
      '#options' => $this->getOptions('from'),
      '#default_value' => $plugin_configuration['from'],
      '#weight' => -9,
    ];
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#options' => $this->getOptions('entity_type'),
      '#default_value' => $plugin_configuration['entity_type'],
      '#weight' => -7,
    ];
    $form['entity_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity ID'),
      '#default_value' => $plugin_configuration['entity_id'],
      '#weight' => -6,
    ];
    $form['revision_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Revision ID'),
      '#default_value' => $plugin_configuration['revision_id'],
      '#weight' => -5,
    ];
    $form['properties'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Property values'),
      '#default_value' => $plugin_configuration['properties'],
      '#description' => $this->t('A key-value list of raw field values of the entity to load. This will only be used when loading by properties is selected above. Supports tokens. Set one value per line. Example:<em><br/>field_mynumber: 1</em>'),
    ];
    $form['langcode'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => $this->getOptions('langcode'),
      '#default_value' => $plugin_configuration['langcode'],
      '#weight' => -4,
    ];
    $form['latest_revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load latest revision'),
      '#default_value' => $plugin_configuration['latest_revision'],
      '#weight' => -3,
    ];
    $form['unchanged'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load unchanged values'),
      '#default_value' => $plugin_configuration['unchanged'],
      '#weight' => -2,
    ];
    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array &$plugin_configuration
   *   The plugin configuration where to put in the submitted form values.
   * @param array &$form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateConfigurationForm(array &$plugin_configuration, array &$form, FormStateInterface $form_state): void {}

  /**
   * Form submission handler.
   *
   * @param array &$plugin_configuration
   *   The plugin configuration where to put in the submitted form values.
   * @param array &$form
   *   The current form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitConfigurationForm(array &$plugin_configuration, array &$form, FormStateInterface $form_state): void {
    $plugin_configuration['from'] = $form_state->getValue('from');
    $plugin_configuration['entity_type'] = $form_state->getValue('entity_type');
    $plugin_configuration['entity_id'] = $form_state->getValue('entity_id');
    $plugin_configuration['revision_id'] = $form_state->getValue('revision_id');
    $plugin_configuration['properties'] = $form_state->getValue('properties');
    $plugin_configuration['langcode'] = $form_state->getValue('langcode');
    $plugin_configuration['latest_revision'] = $form_state->getValue('latest_revision');
    $plugin_configuration['unchanged'] = $form_state->getValue('unchanged');
  }

  /**
   * Get configuration options.
   *
   * @param string $id
   *   The ID for which to provide options.
   *
   * @return array|null
   *   The options, or NULL if it does not provide options for the given ID.
   */
  public function getOptions(string $id): ?array {
    if ($id === 'from') {
      return [
        'current' => $this->t('Current scope'),
        'id' => $this->t('Type and ID (see below)'),
        'properties' => $this->t('Type and properties (see below)'),
      ];
    }
    if ($id === 'entity_type') {
      $entity_types = [];
      foreach ($this->entityTypeManager->getDefinitions() as $type_definition) {
        if ($type_definition->entityClassImplements(ContentEntityInterface::class)) {
          $entity_types[$type_definition->id()] = $type_definition->getLabel();
        }
      }
      return ['_none' => $this->t('- None chosen -')] + $entity_types;
    }
    if ($id === 'langcode') {
      $langcodes = [];
      foreach (\Drupal::languageManager()->getLanguages() as $langcode => $language) {
        $langcodes[$langcode] = $language->getName();
      }
      return [
        '_interface' => $this->t('Interface language'),
      ] + $langcodes;
    }
    return NULL;
  }

  /**
   * Loads the entity by using the currently given plugin configuration.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   (Optional) A passed through entity object.
   * @param array $plugin_configuration
   *   (Optional) The plugin configuration values.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The loaded entity, or NULL if not found.
   *
   * @throws \InvalidArgumentException
   *   When the provided argument is not NULL and not an entity object.
   */
  public function loadEntity($entity = NULL, array $plugin_configuration = []): ?EntityInterface {
    if (!($entity instanceof EntityInterface) && !is_null($entity)) {
      throw new \InvalidArgumentException(sprintf("The entity argument must be an instance of \Drupal\Core\Entity\EntityInterface or NULL, %s given.", gettype($entity)));
    }

    $config = $plugin_configuration + $this->defaultConfiguration();
    $token = $this->tokenServices;

    switch ($config['from']) {

      case 'id':
        $entity = NULL;
        if (!empty($config['entity_type'])
          && $config['entity_type'] !== '_none'
          && $config['entity_id'] !== ''
          && $this->entityTypeManager->hasDefinition($config['entity_type'])) {
            $entity_id = trim($token->replaceClear($config['entity_id']));
            if ($entity_id !== '') {
              $entity = $this->entityTypeManager->getStorage($config['entity_type'])->load($entity_id);
            }
        }
        break;

      case 'properties':
        $entity = NULL;
        if (!empty($config['entity_type'])
          && $config['entity_type'] !== '_none'
          && $config['properties'] !== ''
          && $this->entityTypeManager->hasDefinition($config['entity_type'])) {

          $properties_string = trim($token->replaceClear($config['properties']));
          $properties = [];
          $tok = strtok($properties_string, "\n");
          while ($tok !== false) {
            [$k, $v] = array_merge(explode(':', $tok, 2), ['']);
            if (trim($k) !== '') {
              $properties[trim($k)][] = trim($v);
            }
            $tok = strtok("\n");
          }
          if (!empty($properties)) {
            $entities = $this->entityTypeManager->getStorage($config['entity_type'])->loadByProperties($properties);
            $entity = $entities ? reset($entities) : NULL;
          }
        }
        break;

    }

    if ($config['unchanged'] === Conditions::OPTION_YES) {
      if (!isset($entity->original) && !$entity->isNew()) {
        /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
        $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
        $entity->original = $storage->loadUnchanged($entity->id());
      }
      $entity = $entity->original ?? NULL;
    }

    if ($entity instanceof TranslatableInterface) {
      $langcode = $config['langcode'] === '_interface' ? \Drupal::languageManager()->getCurrentLanguage()->getId() : $config['langcode'];
      if (!(($langcode === LanguageInterface::LANGCODE_DEFAULT) && $entity->isDefaultTranslation()) && ($entity->language()->getId() !== $langcode)) {
        if ($entity->hasTranslation($langcode)) {
          $entity = $entity->getTranslation($langcode);
        }
        elseif ($config['langcode'] !== '_interface') {
          $entity = NULL;
        }
      }
    }

    if ($entity instanceof RevisionableInterface) {
      /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
      if (($config['latest_revision'] === Conditions::OPTION_YES) && !$entity->isLatestRevision()) {
        $entity = $storage->loadRevision($storage->getLatestRevisionId($entity->id()));
      }
      elseif ($config['revision_id'] !== '') {
        $entity = NULL;
        $revision_id = trim($token->replaceClear($config['revision_id']));
        if ($revision_id !== '') {
          $entity = $storage->loadRevision($revision_id);
        }
      }
    }

    return $entity;
  }

}
