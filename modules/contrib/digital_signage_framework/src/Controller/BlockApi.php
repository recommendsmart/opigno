<?php

namespace Drupal\digital_signage_framework\Controller;

use Drupal\block\Entity\Block;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the Block controller.
 */
class BlockApi implements ContainerInjectionInterface {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\block\BlockViewBuilder
   */
  protected $blockViewBuilder;

  public function __construct(EntityTypeManagerInterface $entityTypeManager, RendererInterface $renderer) {
    $this->blockViewBuilder = $entityTypeManager->getViewBuilder('block');
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): BlockApi {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function request($id): Response {
    $block = Block::load($id);
    $build = $this->blockViewBuilder->view($block);
    $content = $this->renderer->renderPlain($build);
    return new Response($content);
  }

}
