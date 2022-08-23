<?php

namespace Drupal\tms;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
class TicketStorage extends SqlContentEntityStorage implements TicketStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(TicketInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {ticket_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {ticket_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

}
