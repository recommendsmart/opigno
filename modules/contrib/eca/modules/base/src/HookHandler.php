<?php

namespace Drupal\eca_base;

use Drupal\eca\Event\BaseHookHandler;

/**
 * The handler for hook implementations within the eca_base.module file.
 */
class HookHandler extends BaseHookHandler {

  /**
   * Trigger ECA's cron event.
   */
  public function cron(): void {
    $this->triggerEvent->dispatchFromPlugin('eca_base:eca_cron');
  }

}
