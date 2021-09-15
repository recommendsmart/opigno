<?php

namespace Drupal\arch_cart\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Cart page controller.
 */
class CartController extends ControllerBase {

  /**
   * The page cache kill switch service.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $pageCacheKillSwitch;

  /**
   * Constructs a CartController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   Form builder service.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $killSwitch
   *   Page cache kill switch service.
   */
  public function __construct(
    FormBuilderInterface $formBuilder,
    KillSwitch $killSwitch
  ) {
    $this->formBuilder = $formBuilder;
    $this->pageCacheKillSwitch = $killSwitch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('page_cache_kill_switch')
    );
  }

  /**
   * Page callback.
   *
   * @return array
   *   Render array.
   */
  public function content() {
    $this->pageCacheKillSwitch->trigger();

    return [
      '#type' => 'container',
      '#attributes' => [],
      '#cache' => [
        'max-age' => 0,
        'contexts' => [
          'user',
          'session',
        ],
      ],
      'form' => $this->formBuilder()->getForm('Drupal\arch_cart\Form\CartForm'),
    ];
  }

}
