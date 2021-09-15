<?php

namespace Drupal\arch_price\Entity\Builder;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of VAT category entities.
 *
 * @see \Drupal\arch_price\Entity\VatCategory
 */
class VatCategoryListBuilder extends DraggableListBuilder {

  /**
   * A configuration instance.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValueStore;

  /**
   * The settings.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * Default VAT category.
   *
   * @var string
   */
  protected $defaultVatCategory;

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'vat_categories';

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new VatCategoryListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   *   Key value store.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    AccountInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    KeyValueFactoryInterface $key_value,
    RendererInterface $renderer,
    MessengerInterface $messenger
  ) {
    parent::__construct(
      $entity_type,
      $entity_type_manager->getStorage($entity_type->id())
    );

    $this->keyValueStore = $key_value;
    $this->keyValue = $this->keyValueStore->get('arch_price.settings');
    $this->defaultVatCategory = $this->keyValue->get('default_vat_category', 'default');

    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type
  ) {
    return new static(
      $entity_type,
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('keyvalue'),
      $container->get('renderer'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vat_category_list';
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = $this->t('Edit VAT category', [], ['context' => 'arch_vat_category']);
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name', [], ['context' => 'arch_vat_category']);
    $header['description'] = $this->t('Description', [], ['context' => 'arch_vat_category']);
    $header['rate'] = $this->t('Rate', [], ['context' => 'arch_vat_category']);

    if (
      $this->currentUser->hasPermission('administer prices')
      && !empty($this->weightKey)
    ) {
      $header['weight'] = $this->t('Weight');
    }

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\arch_price\Entity\VatCategoryInterface $entity */
    $row['label'] = $entity->label();
    if ($entity->id() == $this->defaultVatCategory) {
      $row['label'] .= ' ' . $this->t('(default)', [], ['context' => 'arch_vat_category']);
    }
    $row['description']['data'] = ['#markup' => $entity->getDescription()];
    if ($entity->isCustom()) {
      $row['rate']['data'] = [
        '#markup' => $this->t('Custom', [], ['context' => 'arch_price_vat_rate']),
      ];
    }
    else {
      $row['rate']['data'] = [
        '#markup' => round((float) $entity->getRate() * 100, 2) . '&nbsp;%',
      ];
    }
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();
    // If there are not multiple price types, disable dragging by unsetting the
    // weight key.
    if (count($entities) <= 1) {
      unset($this->weightKey);
    }
    $build = parent::render();

    // If the weight key was unset then the table is in the 'table' key,
    // otherwise in price types. The empty message is only needed if the table
    // is possibly empty, so there is no need to support the price types key
    // here.
    if (isset($build['table'])) {
      $access_control_handler = $this->entityTypeManager->getAccessControlHandler('vat_category');
      $create_access = $access_control_handler->createAccess(NULL, NULL, [], TRUE);
      $this->renderer->addCacheableDependency($build['table'], $create_access);
      if ($create_access->isAllowed()) {
        $build['table']['#empty'] = $this->t(
          'No VAT categories available. <a href=":link">Add VAT category</a>.',
          [':link' => Url::fromRoute('entity.vat_category.add_form')->toString()],
          ['context' => 'arch_vat_category']
        );
      }
      else {
        $build['table']['#empty'] = $this->t('No VAT category available.', [], ['context' => 'arch_vat_category']);
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['vat_categories']['#attributes'] = ['id' => 'vat_category'];
    $form['actions']['submit']['#value'] = $this->t('Save');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->messenger->addStatus($this->t('The configuration options have been saved.'));
  }

}
