<?php

namespace Drupal\arch_order\Entity\Builder;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of order entities.
 *
 * @see \Drupal\arch_order\Entity\Order
 */
class OrderListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new OrderListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    LanguageManagerInterface $language_manager,
    EntityStorageInterface $storage,
    DateFormatterInterface $date_formatter,
    RedirectDestinationInterface $redirect_destination
  ) {
    parent::__construct($entity_type, $storage);

    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
    $this->languageManager = $language_manager;
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
      $container->get('language_manager'),
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // Enable language column and filter if multiple languages are added.
    $header = [
      'oid' => $this->t('Order ID', [], ['context' => 'arch_order']),
      'order_number' => [
        'data' => $this->t('Order number', [], ['context' => 'arch_order']),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'author' => [
        'data' => $this->t('Customer', [], ['context' => 'arch_order']),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'status' => $this->t('Status', [], ['context' => 'arch_order__list']),
      'changed' => [
        'data' => $this->t('Updated', [], ['context' => 'arch_order']),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];
    if ($this->languageManager->isMultilingual()) {
      $header['language_name'] = [
        'data' => $this->t('Language'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
    }
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\arch_order\Entity\OrderInterface $entity */
    $langcode = $entity->language()->getId();
    $uri = $entity->toUrl('canonical');
    $options = $uri->getOptions();
    $uri->setOptions($options);
    $row['oid']['data'] = [
      '#type' => 'link',
      '#title' => $entity->id(),
      '#url' => $uri,
    ];
    $row['order_number'] = [
      '#type' => 'link',
      '#title' => $entity->get('order_number')->getString(),
      '#url' => $uri,
    ];
    $row['author']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];
    $row['status'] = $entity->get('status')->getString();
    $row['changed'] = $this->dateFormatter->format($entity->getChangedTime(), 'short');
    if ($this->languageManager->isMultilingual()) {
      $row['language_name'] = $this->languageManager->getLanguageName($langcode);
    }
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row + parent::buildRow($entity);
  }

}
