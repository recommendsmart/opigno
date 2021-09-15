<?php

namespace Drupal\arch_product\Entity\Builder;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of product entities.
 *
 * @see \Drupal\arch_product\Entity\Product
 */
class ProductListBuilder extends EntityListBuilder {

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
   * Constructs a new ProductListBuilder object.
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
      'title' => $this->t('Name', [], ['context' => 'arch_product']),
      'type' => [
        'data' => $this->t('Type', [], ['context' => 'arch_product__list']),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'author' => [
        'data' => $this->t('Creator', [], ['context' => 'arch_product']),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'status' => $this->t('Status', [], ['context' => 'arch_product']),
      'changed' => [
        'data' => $this->t('Updated', [], ['context' => 'arch_product']),
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
    /** @var \Drupal\arch_product\Entity\ProductInterface $entity */
    $langcode = $entity->language()->getId();
    $uri = $entity->toUrl('canonical');
    $options = $uri->getOptions();
    $uri->setOptions($options);
    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => $uri,
    ];
    $row['type'] = product_get_type_label($entity);
    $row['author']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];
    $row['status'] = $entity->isPublished()
      ? $this->t('published')
      : $this->t('not published');
    $row['changed'] = $this->dateFormatter->format($entity->getChangedTime(), 'short');
    if ($this->languageManager->isMultilingual()) {
      $row['language_name'] = $this->languageManager->getLanguageName($langcode);
    }
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row + parent::buildRow($entity);
  }

}
