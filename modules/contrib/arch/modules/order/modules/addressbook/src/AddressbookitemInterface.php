<?php

namespace Drupal\arch_addressbook;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining the AddressBookItem entity.
 */
interface AddressbookitemInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface, RevisionLogInterface {

  /**
   * Get value as OrderAddressDataInterface instance.
   *
   * @return \Drupal\arch_order\OrderAddressDataInterface
   *   OrderAddressDataInterface instance.
   */
  public function toOrderAddress();

}
