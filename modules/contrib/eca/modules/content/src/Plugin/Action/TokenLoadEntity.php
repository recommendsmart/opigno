<?php

namespace Drupal\eca_content\Plugin\Action;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\eca\Plugin\OptionsInterface;
use Drupal\eca\Service\Conditions;

/**
 * Load an entity into the token environment.
 *
 * @Action(
 *   id = "eca_token_load_entity",
 *   label = @Translation("Token: load entity"),
 *   type = "entity"
 * )
 */
class TokenLoadEntity extends ConfigurableActionBase implements OptionsInterface {

  /**
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected ?EntityInterface $entity;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): void {
    if (is_null($entity) || $entity instanceof EntityInterface) {
      $entity = $this->loadEntity($entity);
    }
    else {
      $entity = NULL;
    }
    if (!$entity) {
      return;
    }
    $token = $this->tokenServices;
    $config = &$this->configuration;
    $tokenName = empty($config['token_name']) ? $token->getTokenType($entity) : $config['token_name'];
    if ($tokenName) {
      $token->addTokenData($tokenName, $entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'token_name' => '',
      'from' => 'current',
      'entity_type' => '_none',
      'entity_id' => NULL,
      'revision_id' => NULL,
      'langcode' => '_interface',
      'latest_revision' => FALSE,
      'unchanged' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['token_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of token'),
      '#default_value' => $this->configuration['token_name'],
      '#weight' => -10,
    ];
    $form['from'] = [
      '#type' => 'select',
      '#title' => $this->t('Load entity from'),
      '#options' => $this->getOptions('from'),
      '#default_value' => $this->configuration['from'],
      '#weight' => -9,
    ];
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#options' => $this->getOptions('entity_type'),
      '#default_value' => $this->configuration['entity_type'],
      '#weight' => -7,
    ];
    $form['entity_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity ID'),
      '#default_value' => $this->configuration['entity_id'],
      '#weight' => -6,
    ];
    $form['revision_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Revision ID'),
      '#default_value' => $this->configuration['revision_id'],
      '#weight' => -5,
    ];
    $form['langcode'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => $this->getOptions('langcode'),
      '#default_value' => $this->configuration['langcode'],
      '#weight' => -4,
    ];
    $form['latest_revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load latest revision'),
      '#default_value' => $this->configuration['latest_revision'],
      '#weight' => -3,
    ];
    $form['unchanged'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load unchanged values'),
      '#default_value' => $this->configuration['unchanged'],
      '#weight' => -2,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['token_name'] = $form_state->getValue('token_name');
    $this->configuration['from'] = $form_state->getValue('from');
    $this->configuration['entity_type'] = $form_state->getValue('entity_type');
    $this->configuration['entity_id'] = $form_state->getValue('entity_id');
    $this->configuration['revision_id'] = $form_state->getValue('revision_id');
    $this->configuration['langcode'] = $form_state->getValue('langcode');
    $this->configuration['latest_revision'] = $form_state->getValue('latest_revision');
    $this->configuration['unchanged'] = $form_state->getValue('unchanged');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(string $id): ?array {
    if ($id === 'from') {
      return [
        'current' => $this->t('Current scope'),
        'id' => $this->t('Type and ID (see below)'),
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
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (is_null($object) || $object instanceof EntityInterface) {
      $object = $this->loadEntity($object);
    }
    $access_result = parent::access($object, $account, TRUE);
    if ($access_result->isAllowed() && $object instanceof EntityInterface) {
      $access_result = $object->access('view', $account, TRUE);
    }
    return $return_as_object ? $access_result : $access_result->isAllowed();
  }

  /**
   * Loads the entity by using the currently given plugin configuration.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   (Optional) A passed through entity object.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The loaded entity, or NULL if not found.
   */
  protected function loadEntity(EntityInterface $entity = NULL): ?EntityInterface {
    $config = &$this->configuration;
    $token = $this->tokenServices;
    if ($config['from'] === 'id') {
      $entity = NULL;
      if (!empty($config['entity_type'])
        && $config['entity_type'] !== '_none'
        && !empty($config['entity_id'])
        && $this->entityTypeManager->hasDefinition($config['entity_type'])) {
        $entity_id = trim($token->replaceClear($config['entity_id']));
        if ($entity_id !== '') {
          $entity = $this->entityTypeManager->getStorage($config['entity_type'])->load($entity_id);
        }
      }
    }
    if ($config['unchanged'] === Conditions::OPTION_YES) {
      /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
      $entity = $storage->loadUnchanged($entity->id());
    }
    if (!empty($config['langcode']) && $entity instanceof TranslatableInterface) {
      $langcode = $config['langcode'] === '_interface' ? \Drupal::languageManager()->getCurrentLanguage()->getId() : $config['langcode'];
      if ($entity->language()->getId() !== $config['langcode']) {
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
      elseif (!empty($config['revision_id'])) {
        $entity = NULL;
        $revision_id = trim($token->replaceClear($config['revision_id']));
        if (!empty($revision_id)) {
          $entity = $storage->loadRevision($config['revision_id']);
        }
      }
    }
    $this->entity = $entity;
    return $this->entity ?? NULL;
  }

}
