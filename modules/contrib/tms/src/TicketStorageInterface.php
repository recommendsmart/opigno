<?php

namespace Drupal\tms;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\tms\Entity\TicketInterface;

/**
 * Defines the storage handler class for Ticket entities.
 *
 * This extends the base storage class, adding required special handling for
 * Ticket entities.
 *
 * @ingroup tms
 */
interface TicketStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Ticket revision IDs for a specific Ticket.
   *
   * @param \Drupal\tms\Entity\TicketInterface $entity
   *   The Ticket entity.
   *
   * @return int[]
   *   Ticket revision IDs (in ascending order).
   */
  public function revisionIds(TicketInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Ticket author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Ticket revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

}
