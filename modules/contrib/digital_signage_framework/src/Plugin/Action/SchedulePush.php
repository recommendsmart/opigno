<?php

namespace Drupal\digital_signage_framework\Plugin\Action;

/**
 * Pushes schedules to devices.
 *
 * @Action(
 *   id = "digital_signage_schedule_push",
 *   label = @Translation("Push schedules"),
 *   type = "digital_signage_device",
 *   confirm_form_route_name = "digital_signage_device.schedule_push"
 * )
 */
class SchedulePush extends Base {

}
