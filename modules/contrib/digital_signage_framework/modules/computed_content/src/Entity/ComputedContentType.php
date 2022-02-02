<?php

namespace Drupal\digital_signage_computed_content\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the digsig_computed_content type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "digsig_computed_content_type",
 *   label = @Translation("Computed content type"),
 *   handlers = {
 *     "form" = {
 *       "edit" = "Drupal\digital_signage_computed_content\Form\ComputedContentType",
 *     },
 *     "list_builder" = "Drupal\digital_signage_computed_content\ComputedContentTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer digsig_computed_content types",
 *   bundle_of = "digsig_computed_content",
 *   config_prefix = "digsig_computed_content_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/digsig_computed_content_types/manage/{digsig_computed_content_type}",
 *     "collection" = "/admin/structure/digsig_computed_content_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *   }
 * )
 */
class ComputedContentType extends ConfigEntityBundleBase {

  /**
   * The machine name of this digsig_computed_content type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the digsig_computed_content type.
   *
   * @var string
   */
  protected $label;

}
