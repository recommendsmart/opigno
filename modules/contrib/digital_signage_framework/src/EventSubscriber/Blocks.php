<?php

namespace Drupal\digital_signage_framework\EventSubscriber;

use Drupal\block\BlockRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\digital_signage_framework\DigitalSignageFrameworkEvents;
use Drupal\digital_signage_framework\Event\Overlays;
use Drupal\digital_signage_framework\Event\Underlays;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Digital signage event subscriber for blocks.
 */
class Blocks implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The block repository.
   *
   * @var \Drupal\block\BlockRepositoryInterface
   */
  protected $blockRepository;

  /**
   * @var \Drupal\block\BlockViewBuilder
   */
  protected $blockViewBuilder;

  /**
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * @var \Drupal\Core\Theme\ActiveTheme
   */
  protected $currentTheme;

  /**
   * @var \Drupal\Core\Theme\ActiveTheme
   */
  protected $defaultTheme;

  /**
   * Blocks constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\block\BlockRepositoryInterface $blockRepository
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $themeInitialization
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RendererInterface $renderer, BlockRepositoryInterface $blockRepository, ThemeManagerInterface $themeManager, ThemeHandlerInterface $themeHandler, ThemeInitializationInterface $themeInitialization) {
    $this->renderer = $renderer;
    $this->blockRepository = $blockRepository;
    $this->blockViewBuilder = $entityTypeManager->getViewBuilder('block');
    $this->themeManager = $themeManager;
    $this->currentTheme = $this->themeManager->getActiveTheme();
    $this->defaultTheme = $themeInitialization->initTheme($themeHandler->getDefault());
  }


  protected function renderBlocks($region): array {
    $this->themeManager->setActiveTheme($this->defaultTheme);

    $content = [];
    $cacheable_metadata_list = [];
    foreach ($this->blockRepository->getVisibleBlocksPerRegion($cacheable_metadata_list) as $theme_region => $blocks) {
      if (!empty($blocks) && $theme_region === $region) {
        /** @var \Drupal\block\Entity\Block $block */
        foreach ($blocks as $block) {
          $build = $this->blockViewBuilder->view($block);
          $content[] = [
            'id' => $block->id(),
            'label' => $block->label(),
            'content' => $this->renderer->renderPlain($build),
            'attached' => $build['#attached'] ?? [],
          ];
        }
      }
    }
    $this->themeManager->setActiveTheme($this->currentTheme);

    return $content;
  }

  /**
   * @param \Drupal\digital_signage_framework\Event\Underlays $event
   */
  public function onUnderlays(Underlays $event): void {
    foreach ($this->renderBlocks('digital_signage_underlays') as $renderBlock) {
      $event->addUnderlay($renderBlock['id'], $renderBlock['label'], $renderBlock['content'], $renderBlock['attached']);
    }
  }

  /**
   * @param \Drupal\digital_signage_framework\Event\Overlays $event
   */
  public function onOverlays(Overlays $event): void {
    foreach ($this->renderBlocks('digital_signage_overlays') as $renderBlock) {
      $event->addOverlay($renderBlock['id'], $renderBlock['label'], $renderBlock['content'], $renderBlock['attached']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      DigitalSignageFrameworkEvents::OVERLAYS => ['onOverlays'],
      DigitalSignageFrameworkEvents::UNDERLAYS => ['onUnderlays'],
    ];
  }

}
