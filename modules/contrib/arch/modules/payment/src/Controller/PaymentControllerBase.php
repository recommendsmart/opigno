<?php

namespace Drupal\arch_payment\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Payment controller base.
 *
 * @package Drupal\arch_payment\Controller
 */
abstract class PaymentControllerBase extends ControllerBase implements PaymentControllerInterface {

  /**
   * Magic method: Insert beforeAction before every method call.
   *
   * @param string $name
   *   Name of the method which has been called.
   * @param array|mixed $arguments
   *   Array of arguments passed to the method.
   *
   * @return mixed
   *   Response of the called method.
   */
  public function __call($name, $arguments) {
    $this->beforeAction($name, $arguments);

    if (method_exists($this, $name)) {
      return call_user_func_array([$this, $name], $arguments);
    }
  }

  /**
   * Run custom scripts before a method being called.
   *
   * @param string $name
   *   Name of the method which has been called.
   * @param array|mixed $arguments
   *   Array of arguments passed to the method.
   */
  private function beforeAction($name, $arguments) {
    $this->logAction($name, $arguments);
  }

  /**
   * Log payment actions on system-level not to have implemented by children.
   *
   * @param string $name
   *   Name of the method which has been called.
   * @param array|mixed $arguments
   *   Array of arguments passed to the method.
   */
  protected function logAction($name, $arguments) {
    if (substr($name, 0, 7) === 'payment') {
      // @todo Implement LOGGER.
    }
  }

}
