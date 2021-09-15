<?php

namespace Drupal\arch_addressbook\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\user\Entity\User;

/**
 * Provides a list controller for arch_addressbook entity.
 */
class AddressbookitemListBuilder extends EntityListBuilder {

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * AddressbookitemListBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Entity Type service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   Entity Storage service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage) {
    parent::__construct($entity_type, $storage);

    $this->limit = 30;

    // @codingStandardsIgnoreStart
    /** @var \Drupal\Core\Render\Renderer renderer */
    $this->renderer = \Drupal::service('renderer');
    // @codingStandardsIgnoreEnd
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_ids = $this->getEntityIdsOverridden();
    return $this->storage->loadMultiple($entity_ids);
  }

  /**
   * Loads entity IDs using a pager sorted by the entity id.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIdsOverridden() {
    $query = $this->getStorage()->getQuery();

    $headers = $this->buildHeader();
    $query->tableSort($headers);
    if (empty(\Drupal::request()->get('order'))) {
      $query->sort('changed', 'DESC');
    }
    $query->sort($this->entityType->getKey('id'), 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the Addressbookitem list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = '';
    $header['user'] = $this->t('User', [], ['context' => 'arch_addressbook']);
    $header['title'] = $this->t('Label', [], ['context' => 'arch_addressbook']);
    $header['address'] = $this->t('Address', [], ['context' => 'arch_addressbook']);
    $header['vat_id'] = $this->t('VAT ID', [], ['context' => 'arch_addressbook']);
    $header['created'] = $this->t('Created', [], ['context' => 'arch_addressbook']);
    $header['changed'] = [
      'field' => 'changed',
      'specifier' => 'changed',
      'data' => $this->t('Changed', [], ['context' => 'arch_addressbook']),
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\arch_addressbook\Entity\Addressbookitem $entity */
    $row['id'] = $entity->toLink('#' . $entity->id())->toString();
    $user = User::load($entity->get('user_id')->getString());
    $row['user'] = Link::createFromRoute(
      $user->getEmail(),
      'entity.user.canonical',
      ['user' => $user->id()]
    )->toString();
    $row['title'] = $entity->get('title')->getString();

    $row['address'] = '';
    $address = $entity->get('address')->first()->view('default');
    if (!empty($address)) {
      $row['address'] = $this->renderer->render($address);
    }
    $row['vat_id'] = $entity->get('vat_id')->getString();
    $row['created'] = date('Y-m-d H:i:s', $entity->get('created')->getString());
    $row['changed'] = date('Y-m-d H:i:s', $entity->get('changed')->getString());

    return $row + parent::buildRow($entity);
  }

}
