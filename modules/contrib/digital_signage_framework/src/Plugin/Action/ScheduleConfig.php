<?php

namespace Drupal\digital_signage_framework\Plugin\Action;

/**
 * Push temporary configuration to devices.
 *
 * @Action(
 *   id = "digital_signage_schedule_config",
 *   label = @Translation("Push temporary configuration"),
 *   type = "digital_signage_device",
 *   confirm_form_route_name = "digital_signage_device.schedule_config"
 * )
 */
class ScheduleConfig extends Base {

}
