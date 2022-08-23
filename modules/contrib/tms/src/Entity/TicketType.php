<?php

namespace Drupal\tms\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Ticket type entity.
 *
 * @ConfigEntityType(
 *   id = "ticket_type",
 *   label = @Translation("Ticket type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\tms\TicketTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\tms\Form\TicketTypeForm",
 *       "edit" = "Drupal\tms\Form\TicketTypeForm",
 *       "delete" = "Drupal\tms\Form\TicketTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\tms\TicketTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "ticket_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "ticket",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *   }, 
 *   links = {
 *     "canonical" = "/admin/structure/ticket_type/{ticket_type}",
 *     "add-form" = "/admin/structure/ticket_type/add",
 *     "edit-form" = "/admin/structure/ticket_type/{ticket_type}/edit",
 *     "delete-form" = "/admin/structure/ticket_type/{ticket_type}/delete",
 *     "collection" = "/admin/structure/ticket_type"
 *   }
 * )
 */
class TicketType extends ConfigEntityBundleBase implements TicketTypeInterface {

  /**
   * The Ticket type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Ticket type label.
   *
   * @var string
   */
  protected $label;

}
