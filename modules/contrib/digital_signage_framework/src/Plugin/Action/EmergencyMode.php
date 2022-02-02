<?php

namespace Drupal\digital_signage_framework\Plugin\Action;

/**
 * Enable/disable emergency mode on devices.
 *
 * @Action(
 *   id = "digital_signage_device_emergency_mode",
 *   label = @Translation("Emergency mode"),
 *   type = "digital_signage_device",
 *   confirm_form_route_name = "digital_signage_device.emergency_mode"
 * )
 */
class EmergencyMode extends Base {

}
