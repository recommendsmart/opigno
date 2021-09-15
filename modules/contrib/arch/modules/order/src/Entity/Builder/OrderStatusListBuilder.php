<?php

namespace Drupal\arch_order\Entity\Builder;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of order status entities.
 *
 * @see \Drupal\arch_order\Entity\OrderStatus
 */
class OrderStatusListBuilder extends DraggableListBuilder {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'order_status';

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

  /**
   * Constructs a new OrderStatusListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage handler class.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    ConfigFactoryInterface $config_factory,
    MessengerInterface $messenger
  ) {
    parent::__construct($entity_type, $storage);
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = $this->storage->loadMultiple();

    // Sort the entities using the entity class's sort() method.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, [$this->entityType->getClass(), 'sort']);
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'order_status_admin_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'label' => $this->t('Name', [], ['context' => 'arch_order_status']),
      'description' => $this->t('Description', [], ['context' => 'arch_order_status']),
      'default' => $this->t('Default', [], ['context' => 'arch_order_status']),
      'locked' => $this->t('Locked', [], ['context' => 'arch_order_status']),
    ] + parent::buildHeader();
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['description']['#markup'] = $entity->getDescription();
    $row['default']['#markup'] = $entity->getIsDefault() ? '&#10003;' : 'x';
    $row['locked']['#markup'] = $entity->isLocked() ? '&#10003;' : 'x';
    $row += parent::buildRow($entity);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form[$this->entitiesKey]['#order_statuses'] = $this->entities;
    $form['actions']['submit']['#value'] = $this->t('Save configuration');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $first_key = key($form_state->getValue('order_status'));
    $this->messenger->addStatus($this->t('Configuration saved.'));
    // Force the redirection to the page with the language we have just
    // selected as default.
    $form_state->setRedirectUrl($this->entities[$first_key]->toUrl('collection', ['order_status' => $this->entities[$first_key]]));
  }

}
