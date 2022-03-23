<?php

namespace Drupal\eca_misc\Plugin\ECA\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\DrupalKernelInterface;
use Drupal\eca\Plugin\ECA\Event\EventBase;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Plugin implementation of the ECA Events for the kernel.
 *
 * @EcaEvent(
 *   id = "kernel",
 *   deriver = "Drupal\eca_misc\Plugin\ECA\Event\KernelEventDeriver"
 * )
 */
class KernelEvent extends EventBase {

  /**
   * @return array[]
   */
  public static function actions(): array {
    return [
      'view' => [
        'label' => 'Controller does not return a Response instance',
        'drupal_id' => KernelEvents::VIEW,
        'drupal_event_class' => ViewEvent::class,
      ],
      'controller' => [
        'label' => 'Controller found to handle request',
        'drupal_id' => KernelEvents::CONTROLLER,
        'drupal_event_class' => ControllerEvent::class,
      ],
      'controller_arguments' => [
        'label' => 'Controller arguments have been resolved',
        'drupal_id' => KernelEvents::CONTROLLER_ARGUMENTS,
        'drupal_event_class' => ControllerArgumentsEvent::class,
      ],
      'exception' => [
        'label' => 'Uncaught exception',
        'drupal_id' => KernelEvents::EXCEPTION,
        'drupal_event_class' => ExceptionEvent::class,
      ],
      'finish_request' => [
        'label' => 'Response for request created',
        'drupal_id' => KernelEvents::FINISH_REQUEST,
        'drupal_event_class' => FinishRequestEvent::class,
      ],
      'request' => [
        'label' => 'Start dispatching request',
        'drupal_id' => KernelEvents::REQUEST,
        'drupal_event_class' => RequestEvent::class,
      ],
      'response' => [
        'label' => 'Response created',
        'drupal_id' => KernelEvents::RESPONSE,
        'drupal_event_class' => ResponseEvent::class,
      ],
      'terminate' => [
        'label' => 'Response was sent',
        'drupal_id' => KernelEvents::TERMINATE,
        'drupal_event_class' => TerminateEvent::class,
      ],
      'container_initialize_subrequest_finished' => [
        'label' => 'Service container finished initializing',
        'drupal_id' => DrupalKernelInterface::CONTAINER_INITIALIZE_SUBREQUEST_FINISHED,
        'drupal_event_class' => Event::class,
      ],
    ];
  }

}
