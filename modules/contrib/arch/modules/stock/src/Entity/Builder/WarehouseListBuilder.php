<?php

namespace Drupal\arch_stock\Entity\Builder;

use Drupal\arch_product\Entity\ProductAvailability;
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
 * Defines a class to build a listing of warehouse entities.
 *
 * @see \Drupal\arch_stock\Entity\Warehouse
 */
class WarehouseListBuilder extends DraggableListBuilder {

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
   * Default warehouse.
   *
   * @var string
   */
  protected $defaultWarehouse;

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'warehouses';

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
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
   * Constructs a new WarehouseListBuilder object.
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
    $this->keyValue = $this->keyValueStore->get('arch_stock.settings');
    $this->defaultWarehouse = $this->keyValue->get('default_warehouse', 'default');

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
    return 'warehouse_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name', [], ['context' => 'arch_stock_warehouse']);
    $header['description'] = $this->t('Description', [], ['context' => 'arch_stock_warehouse']);
    $header['allow_negative'] = $this->t('Allow over booking', [], ['context' => 'arch_stock_warehouse']);

    if (
      $this->currentUser->hasPermission('administer stock')
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
    /** @var \Drupal\arch_stock\Entity\WarehouseInterface $entity */
    $row['label'] = $entity->label();
    if ($entity->id() == $this->defaultWarehouse) {
      $row['label'] .= ' ' . $this->t('(default)', [], ['context' => 'arch_stock_warehouse']);
    }
    $row['description']['data'] = ['#markup' => $entity->getDescription()];

    if (!$entity->allowNegative()) {
      $row['allow_negative']['data'] = ['#markup' => $this->t('No')];
    }
    else {
      $row['allow_negative']['data'] = [
        '#type' => 'inline_template',
        '#template' => '{{ allow }}{% if change_to %}<br/>Availability: {{ change_to }}{% endif %}',
        '#context' => [
          'allow' => $this->t('Yes'),
          'change_to' => NULL,
        ],
      ];

      $availabilities = ProductAvailability::getOptions();
      if (
        $entity->getOverBookedAvailability()
        && !empty($availabilities[$entity->getOverBookedAvailability()])
      ) {
        $row['allow_negative']['data']['#context']['change_to'] = $availabilities[$entity->getOverBookedAvailability()];
      }
      else {
        $row['allow_negative']['data']['#context']['change_to'] = $this->t('Do not change', [], ['context' => 'arch_stock_warehouse']);
      }

    }
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();
    // If there are not multiple warehouses, disable dragging by unsetting the
    // weight key.
    if (count($entities) <= 1) {
      unset($this->weightKey);
    }
    $build = parent::render();

    // If the weight key was unset then the table is in the 'table' key,
    // otherwise in warehouses. The empty message is only needed if the table
    // is possibly empty, so there is no need to support the warehouses key
    // here.
    if (isset($build['table'])) {
      $access_control_handler = $this->entityTypeManager->getAccessControlHandler('warehouse');
      $create_access = $access_control_handler->createAccess(NULL, NULL, [], TRUE);
      $this->renderer->addCacheableDependency($build['table'], $create_access);
      if ($create_access->isAllowed()) {
        $build['table']['#empty'] = $this->t(
          'No warehouses available. <a href=":link">Add warehouse</a>.',
          [':link' => Url::fromRoute('entity.warehouse.add_form')->toString()],
          ['context' => 'arch_stock']
        );
      }
      else {
        $build['table']['#empty'] = $this->t('No warehouse available.', [], ['context' => 'arch_stock']);
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['warehouse']['#attributes'] = ['id' => 'warehouse'];
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
