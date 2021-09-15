<?php

namespace Drupal\arch_addressbook\Services;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * User addresses service interface.
 *
 * @package Drupal\arch_addressbook\Services
 */
interface UserAddressesServiceInterface extends ContainerInjectionInterface {

  /**
   * Get user addresses.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   User object to get a specific user's addresses, or current user used.
   *
   * @return \Drupal\arch_addressbook\AddressbookitemInterface[]
   *   AddressbookItem list.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getByUser(AccountInterface $account = NULL);

  /**
   * Load AddresbookItem entities by property values.
   *
   * @param array $values
   *   An associative array where the keys are the property names and the
   *   values are the values those properties must have.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   Owner account.
   *
   * @return \Drupal\arch_addressbook\AddressbookitemInterface[]
   *   List of found items.
   */
  public function getByProperties(array $values, AccountInterface $account = NULL);

}
