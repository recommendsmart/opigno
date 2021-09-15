<?php

namespace Drupal\arch_addressbook\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * User addresses service.
 *
 * @package Drupal\arch_addressbook\Services
 */
class UserAddressesService implements UserAddressesServiceInterface {

  /**
   * EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * CurrentUser service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Address book item storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $addressStorage;

  /**
   * UserAddressesService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   EntityType Manager object.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   CurrentUser service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->addressStorage = $entity_type_manager->getStorage('addressbookitem');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getByUser(AccountInterface $account = NULL) {
    return $this->getByProperties([], $account);
  }

  /**
   * {@inheritdoc}
   */
  public function getByProperties(array $values, AccountInterface $account = NULL) {
    if (empty($account)) {
      $account = $this->currentUser;
    }

    $values['user_id'] = $account->id();

    /** @var \Drupal\arch_addressbook\AddressbookitemInterface[] $result */
    $result = $this->addressStorage->loadByProperties($values);

    return $result;
  }

}
