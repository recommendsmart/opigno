<?php

namespace Drupal\digital_signage_framework\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Device type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "digital_signage_device_type",
 *   label = @Translation("Digital signage device type"),
 *   handlers = {
 *     "form" = {
 *       "edit" = "Drupal\digital_signage_framework\Form\DeviceType",
 *     },
 *     "list_builder" = "Drupal\digital_signage_framework\DeviceTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer digital signage device types",
 *   bundle_of = "digital_signage_device",
 *   config_prefix = "digital_signage_device_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/digital_signage_device_types/manage/{digital_signage_device_type}",
 *     "collection" = "/admin/structure/digital_signage_device_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *   }
 * )
 */
class DeviceType extends ConfigEntityBundleBase {

  /**
   * The machine name of this device type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the device type.
   *
   * @var string
   */
  protected $label;

}
